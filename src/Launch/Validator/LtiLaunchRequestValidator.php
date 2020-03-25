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
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Message;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\GuzzleJwksFetcher;
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
    /** @var DeploymentRepositoryInterface */
    private $deploymentRepository;

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

    /** @var string[] */
    private $failures = [];

    public function __construct(
        DeploymentRepositoryInterface $deploymentRepository,
        NonceRepositoryInterface $nonceRepository,
        JwksFetcherInterface $jwksFetcher = null,
        Signer $signer = null
    ) {
        $this->deploymentRepository = $deploymentRepository;
        $this->nonceRepository = $nonceRepository;
        $this->fetcher = $jwksFetcher ?? new GuzzleJwksFetcher();
        $this->signer = $signer ?? new Sha256();
        $this->parser = new Parser(new AssociativeDecoder());
    }

    /**
     * @throws LtiException
     */
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

            $deployment = $this->deploymentRepository->find($ltiMessage->getDeploymentId());

            if (null === $deployment)  {
                throw new LtiException(sprintf('No deployment found with id %s', $ltiMessage->getDeploymentId()));
            }

            $this
                ->validateMessageSignature($deployment, $ltiMessage)
                ->validateMessageExpiry($ltiMessage)
                ->validateMessageNonce($ltiMessage)
                ->validateMessageIssuer($deployment, $ltiMessage)
                ->validateMessageAudience($deployment, $ltiMessage)
                ->validateStateSignature($deployment, $oidcState)
                ->validateStateExpiry($oidcState);

            return new LtiLaunchRequestValidationResult($ltiMessage, $this->successes, $this->failures);

        } catch (LtiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('LTI message validation failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @throws LtiException
     */
    private function validateMessageSignature(DeploymentInterface $deployment, LtiMessageInterface $ltiMessage): self
    {
        if (null === $deployment->getPlatformKeyChain()) {
            $key = $this->fetcher->fetchKey(
                $deployment->getPlatformJwksUrl(),
                $ltiMessage->getToken()->getHeader(MessageInterface::HEADER_KID)
            );
        } else{
            $key = $deployment->getPlatformKeyChain()->getPublicKey();
        }

        if(!$ltiMessage->getToken()->verify($this->signer, $key)) {
            $this->addFailure('JWT id_token signature validation failure');
        } else {
            $this->addSuccess('JWT id_token signature validation success');
        }

        return $this;
    }

    private function validateMessageExpiry(LtiMessageInterface $ltiMessage): self
    {
        if ($ltiMessage->getToken()->isExpired()) {
            $this->addFailure('JWT id_token is expired');
        } else {
            $this->addSuccess('JWT id_token is not expired');
        }

        return $this;
    }

    private function validateMessageNonce(LtiMessageInterface $ltiMessage): self
    {
        $nonceValue = $ltiMessage->getMandatoryClaim(MessageInterface::CLAIM_NONCE);

        $nonce = $this->nonceRepository->find($nonceValue);

        if (null !== $nonce) {
            if (!$nonce->isExpired()) {
                $this->addFailure('JWT id_token nonce already used');
            } else {
                $this->addSuccess('JWT id_token nonce already used but expired');
            }
        } else {
            $this->nonceRepository->save(
                new Nonce($nonceValue, Carbon::now()->addSeconds(NonceGeneratorInterface::DEFAULT_TTL))
            );

            $this->addSuccess('JWT id_token nonce is valid');
        }

        return $this;
    }

    private function validateMessageIssuer(DeploymentInterface $deployment, LtiMessageInterface $ltiMessage): self
    {
        if ($deployment->getPlatform()->getAudience() != $ltiMessage->getMandatoryClaim(MessageInterface::CLAIM_ISS)) {
            $this->addFailure('JWT id_token iss claim does not match platform audience');
        } else {
            $this->addSuccess('JWT id_token iss claim matches platform audience');
        }

        return $this;
    }

    private function validateMessageAudience(DeploymentInterface $deployment, LtiMessageInterface $ltiMessage): self
    {
        if ($deployment->getClientId() != $ltiMessage->getMandatoryClaim(MessageInterface::CLAIM_AUD)) {
            $this->addFailure('JWT id_token aud claim does not match tool oauth2 client id');
        } else {
            $this->addSuccess('JWT id_token aud claim matches tool oauth2 client id');
        }

        return $this;
    }

    private function validateStateSignature(DeploymentInterface $deployment, MessageInterface $oidcState = null): self
    {
        if (null !== $oidcState) {
            if(!$oidcState->getToken()->verify($this->signer,  $deployment->getToolKeyChain()->getPublicKey())) {
                $this->addFailure('JWT OIDC state signature validation failure');
            } else {
                $this->addSuccess('JWT OIDC state signature validation success');
            }
        }

        return $this;
    }

    private function validateStateExpiry(MessageInterface $oidcState = null): self
    {
        if (null !== $oidcState) {
            if ($oidcState->getToken()->isExpired()) {
                $this->addFailure('JWT OIDC state is expired');
            } else {
                $this->addSuccess('JWT OIDC state is not expired');
            }
        }

        return $this;
    }

    private function addSuccess(string $message): self
    {
        $this->successes[] = $message;

        return $this;
    }

    private function addFailure(string $message): self
    {
        $this->failures[] = $message;

        return $this;
    }

    private function reset(): self
    {
        $this->successes = [];
        $this->failures = [];

        return $this;
    }
}
