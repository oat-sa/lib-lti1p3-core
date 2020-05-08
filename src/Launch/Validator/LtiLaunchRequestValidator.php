<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Launch\Validator;

use Carbon\Carbon;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Message;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation
 */
class LtiLaunchRequestValidator
{
    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var NonceRepositoryInterface */
    private $nonceRepository;

    /** @var JwksFetcherInterface */
    private $fetcher;

    /** @var Signer */
    private $signer;

    /** @var Parser */
    private $parser;

    /** @var string[] */
    private $successes = [];

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        NonceRepositoryInterface $nonceRepository,
        JwksFetcherInterface $jwksFetcher = null,
        Signer $signer = null
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->nonceRepository = $nonceRepository;
        $this->fetcher = $jwksFetcher ?? new JwksFetcher();
        $this->signer = $signer ?? new Sha256();
        $this->parser = new Parser(new AssociativeDecoder());
    }

    public function validate(ServerRequestInterface $request): LtiLaunchRequestValidationResult
    {
        $this->reset();

        try {
            /** @var LtiLaunchRequest $launchRequest */
            $launchRequest = LtiLaunchRequest::fromServerRequest($request);

            $ltiMessage = new LtiMessage($this->parser->parse($launchRequest->getLtiMessage()));

            $oidcState = $launchRequest->getOidcState()
                ? new Message($this->parser->parse($launchRequest->getOidcState()))
                : null;

            $registration = $this->registrationRepository->findByPlatformIssuer(
                $ltiMessage->getMandatoryClaim(MessageInterface::CLAIM_ISS),
                $ltiMessage->getMandatoryClaim(MessageInterface::CLAIM_AUD)
            );

            if (null === $registration) {
                throw new LtiException('No matching registration found');
            }

            $this
                ->validateMessageSignature($registration, $ltiMessage)
                ->validateMessageExpiry($ltiMessage)
                ->validateMessageNonce($ltiMessage)
                ->validateMessageDeploymentId($registration, $ltiMessage)
                ->validateStateSignature($registration, $oidcState)
                ->validateStateExpiry($oidcState);

            return new LtiLaunchRequestValidationResult($registration, $ltiMessage, $this->successes);

        } catch (Throwable $exception) {
            return new LtiLaunchRequestValidationResult(null, null, $this->successes, $exception->getMessage());
        }
    }

    /**
     * @throws LtiException
     */
    private function validateMessageSignature(RegistrationInterface $registration, LtiMessageInterface $ltiMessage): self
    {
        if (null === $registration->getPlatformKeyChain()) {
            $key = $this->fetcher->fetchKey(
                $registration->getPlatformJwksUrl(),
                $ltiMessage->getToken()->getHeader(MessageInterface::HEADER_KID)
            );
        } else {
            $key = $registration->getPlatformKeyChain()->getPublicKey();
        }

        if (!$ltiMessage->getToken()->verify($this->signer, $key)) {
            throw new LtiException('JWT id_token signature validation failure');
        }

        return $this->addSuccess('JWT id_token signature validation success');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageExpiry(LtiMessageInterface $ltiMessage): self
    {
        if ($ltiMessage->getToken()->isExpired()) {
            throw new LtiException('JWT id_token is expired');
        }

        return $this->addSuccess('JWT id_token is not expired');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageNonce(LtiMessageInterface $ltiMessage): self
    {
        $nonceValue = $ltiMessage->getMandatoryClaim(MessageInterface::CLAIM_NONCE);

        $nonce = $this->nonceRepository->find($nonceValue);

        if (null !== $nonce) {
            if (!$nonce->isExpired()) {
                throw new LtiException('JWT id_token nonce already used');
            }

            return $this->addSuccess('JWT id_token nonce already used but expired');
        } else {
            $this->nonceRepository->save(
                new Nonce($nonceValue, Carbon::now()->addSeconds(NonceGeneratorInterface::TTL))
            );

            return $this->addSuccess('JWT id_token nonce is valid');
        }
    }

    /**
     * @throws LtiException
     */
    private function validateMessageDeploymentId(RegistrationInterface $registration, LtiMessageInterface $ltiMessage): self
    {
        if (!$registration->hasDeploymentId($ltiMessage->getDeploymentId())) {
            throw new LtiException('JWT id_token deployment_id claim not valid for this registration');
        }

        return $this->addSuccess('JWT id_token deployment_id claim valid for this registration');
    }

    /**
     * @throws LtiException
     */
    private function validateStateSignature(RegistrationInterface $registration, MessageInterface $oidcState = null): self
    {
        if (null !== $oidcState) {
            if (null === $registration->getToolKeyChain()) {
                throw new LtiException('Tool key chain not configured');
            }

            if (!$oidcState->getToken()->verify($this->signer, $registration->getToolKeyChain()->getPublicKey())) {
                throw new LtiException('JWT OIDC state signature validation failure');
            }

            return $this->addSuccess('JWT OIDC state signature validation success');
        }

        return $this;
    }

    /**
     * @throws LtiException
     */
    private function validateStateExpiry(MessageInterface $oidcState = null): self
    {
        if (null !== $oidcState) {
            if ($oidcState->getToken()->isExpired()) {
                throw new LtiException('JWT OIDC state is expired');
            }

            return $this->addSuccess('JWT OIDC state is not expired');
        }

        return $this;
    }

    private function addSuccess(string $message): self
    {
        $this->successes[] = $message;

        return $this;
    }

    private function reset(): self
    {
        $this->successes = [];

        return $this;
    }
}
