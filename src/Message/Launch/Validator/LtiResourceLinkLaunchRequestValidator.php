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

namespace OAT\Library\Lti1p3Core\Message\Launch\Validator;

use Carbon\Carbon;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Token\LtiMessageToken;
use OAT\Library\Lti1p3Core\Message\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Message\Token\MessageToken;
use OAT\Library\Lti1p3Core\Message\Token\MessageTokenInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
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
class LtiResourceLinkLaunchRequestValidator
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

    public function validate(ServerRequestInterface $request): LtiResourceLinkLaunchRequestValidationResult
    {
        $this->reset();

        try {
            $launchRequest = LtiMessage::fromServerRequest($request);

            $idToken = new LtiMessageToken($this->parser->parse($launchRequest->getMandatoryParameter('id_token')));
            $state = new MessageToken($this->parser->parse($launchRequest->getMandatoryParameter('state')));

            $registration = $this->registrationRepository->findByPlatformIssuer(
                $idToken->getMandatoryClaim(MessageTokenInterface::CLAIM_ISS),
                $idToken->getMandatoryClaim(MessageTokenInterface::CLAIM_AUD)
            );

            if (null === $registration) {
                throw new LtiException('No matching registration found');
            }

            $this
                ->validateMessageExpiry($idToken)
                ->validateMessageKid($idToken)
                ->validateMessageVersion($idToken)
                ->validateMessageType($idToken)
                ->validateMessageRoles($idToken)
                ->validateMessageResourceLinkId($idToken)
                ->validateMessageUserIdentifier($idToken)
                ->validateMessageSignature($registration, $idToken)
                ->validateMessageNonce($idToken)
                ->validateMessageDeploymentId($registration, $idToken)
                ->validateStateExpiry($state)
                ->validateStateSignature($registration, $state);

            return new LtiResourceLinkLaunchRequestValidationResult($registration, $idToken, $state, $this->successes);

        } catch (Throwable $exception) {
            return new LtiResourceLinkLaunchRequestValidationResult(null, null, null, $this->successes, $exception->getMessage());
        }
    }

    /**
     * @throws LtiException
     */
    private function validateMessageKid(LtiMessageTokenInterface $idToken): self
    {
        if (!$idToken->getToken()->hasHeader(LtiMessageTokenInterface::HEADER_KID)) {
            throw new LtiException('JWT id_token kid header is missing');
        }

        return $this->addSuccess('JWT id_token kid header is provided');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageVersion(LtiMessageTokenInterface $idToken): self
    {
        if ($idToken->getVersion() !== LtiMessageInterface::LTI_VERSION) {
            throw new LtiException('JWT id_token version claim is invalid');
        }

        return $this->addSuccess('JWT id_token version claim is valid');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageType(LtiMessageTokenInterface $idToken): self
    {
        if ($idToken->getMessageType() === '') {
            throw new LtiException('JWT id_token message_type claim is invalid');
        }

        return $this->addSuccess('JWT id_token message_type claim is valid');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageRoles(LtiMessageTokenInterface $idToken): self
    {
        if (!is_array($idToken->getToken()->getClaim(LtiMessageTokenInterface::CLAIM_LTI_ROLES))) {
            throw new LtiException('JWT id_token roles claim is invalid');
        }

        return $this->addSuccess('JWT id_token roles claim is valid');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageResourceLinkId(LtiMessageTokenInterface $idToken): self
    {
        if ($idToken->getResourceLink()->getId() === '') {
            throw new LtiException('JWT id_token resource_link id claim is invalid');
        }

        return $this->addSuccess('JWT id_token resource_link id claim is valid');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageUserIdentifier(LtiMessageTokenInterface $idToken): self
    {
        if ($idToken->getUserIdentity() && $idToken->getUserIdentity()->getIdentifier() === '') {
            throw new LtiException('JWT id_token user identifier (sub) claim is invalid');
        }

        return $this->addSuccess('JWT id_token user identifier (sub) claim is valid');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageSignature(RegistrationInterface $registration, LtiMessageTokenInterface $idToken): self
    {
        if (null === $registration->getPlatformKeyChain()) {
            $key = $this->fetcher->fetchKey(
                $registration->getPlatformJwksUrl(),
                $idToken->getToken()->getHeader(LtiMessageTokenInterface::HEADER_KID)
            );
        } else {
            $key = $registration->getPlatformKeyChain()->getPublicKey();
        }

        if (!$idToken->getToken()->verify($this->signer, $key)) {
            throw new LtiException('JWT id_token signature validation failure');
        }

        return $this->addSuccess('JWT id_token signature validation success');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageExpiry(LtiMessageTokenInterface $idToken): self
    {
        if ($idToken->getToken()->isExpired()) {
            throw new LtiException('JWT id_token is expired');
        }

        return $this->addSuccess('JWT id_token is not expired');
    }

    /**
     * @throws LtiException
     */
    private function validateMessageNonce(LtiMessageTokenInterface $idToken): self
    {
        $nonceValue = $idToken->getMandatoryClaim(MessageTokenInterface::CLAIM_NONCE);

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
    private function validateMessageDeploymentId(RegistrationInterface $registration, LtiMessageTokenInterface $idToken): self
    {
        if (!$registration->hasDeploymentId($idToken->getDeploymentId())) {
            throw new LtiException('JWT id_token deployment_id claim not valid for this registration');
        }

        return $this->addSuccess('JWT id_token deployment_id claim valid for this registration');
    }

    /**
     * @throws LtiException
     */
    private function validateStateSignature(RegistrationInterface $registration, MessageTokenInterface $state): self
    {
        if (null === $registration->getToolKeyChain()) {
            throw new LtiException('Tool key chain not configured');
        }

        if (!$state->getToken()->verify($this->signer, $registration->getToolKeyChain()->getPublicKey())) {
            throw new LtiException('JWT OIDC state signature validation failure');
        }

        return $this->addSuccess('JWT OIDC state signature validation success');
    }

    /**
     * @throws LtiException
     */
    private function validateStateExpiry(MessageTokenInterface $state): self
    {
        if ($state->getToken()->isExpired()) {
            throw new LtiException('JWT OIDC state is expired');
        }

        return $this->addSuccess('JWT OIDC state is not expired');
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
