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
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
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
abstract class AbstractLaunchRequestValidator
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

    public function validate(ServerRequestInterface $request): LaunchRequestValidationResult
    {
        $this->reset();

        try {
            $launchRequest = LtiMessage::fromServerRequest($request);

            $payload = new LtiMessagePayload($this->parser->parse($launchRequest->getMandatoryParameter('id_token')));
            $state = new MessagePayload($this->parser->parse($launchRequest->getMandatoryParameter('state')));

            $registration = $this->registrationRepository->findByPlatformIssuer(
                $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_ISS),
                $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_AUD)
            );

            if (null === $registration) {
                throw new LtiException('No matching registration found');
            }

            $this
                ->validatePayloadExpiry($payload)
                ->validatePayloadKid($payload)
                ->validatePayloadVersion($payload)
                ->validatePayloadMessageType($payload)
                ->validatePayloadRoles($payload)
                ->validatePayloadUserIdentifier($payload)
                ->validatePayloadSignature($registration, $payload)
                ->validatePayloadNonce($payload)
                ->validatePayloadDeploymentId($registration, $payload)
                ->validateStateExpiry($state)
                ->validateStateSignature($registration, $state);

            $this->validateSpecifics($registration, $payload, $state);

            return new LaunchRequestValidationResult($registration, $payload, $state, $this->successes);

        } catch (Throwable $exception) {
            return new LaunchRequestValidationResult(null, null, null, $this->successes, $exception->getMessage());
        }
    }

    abstract protected function validateSpecifics(
        RegistrationInterface $registration,
        LtiMessagePayloadInterface $payload,
        MessagePayloadInterface $state
    ): void;

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadKid(LtiMessagePayloadInterface $payload): self
    {
        if (!$payload->getToken()->hasHeader(LtiMessagePayloadInterface::HEADER_KID)) {
            throw new LtiException('JWT id_token kid header is missing');
        }

        return $this->addSuccess('JWT id_token kid header is provided');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadVersion(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getVersion() !== LtiMessageInterface::LTI_VERSION) {
            throw new LtiException('JWT id_token version claim is invalid');
        }

        return $this->addSuccess('JWT id_token version claim is valid');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadMessageType(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getMessageType() === '') {
            throw new LtiException('JWT id_token message_type claim is missing');
        }

        return $this->addSuccess('JWT id_token message_type claim is provided');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadRoles(LtiMessagePayloadInterface $payload): self
    {
        if (!is_array($payload->getToken()->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLES))) {
            throw new LtiException('JWT id_token roles claim is invalid');
        }

        return $this->addSuccess('JWT id_token roles claim is valid');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadUserIdentifier(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getUserIdentity() && $payload->getUserIdentity()->getIdentifier() === '') {
            throw new LtiException('JWT id_token user identifier (sub) claim is invalid');
        }

        return $this->addSuccess('JWT id_token user identifier (sub) claim is valid');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadSignature(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        if (null === $registration->getPlatformKeyChain()) {
            $key = $this->fetcher->fetchKey(
                $registration->getPlatformJwksUrl(),
                $payload->getToken()->getHeader(LtiMessagePayloadInterface::HEADER_KID)
            );
        } else {
            $key = $registration->getPlatformKeyChain()->getPublicKey();
        }

        if (!$payload->getToken()->verify($this->signer, $key)) {
            throw new LtiException('JWT id_token signature validation failure');
        }

        return $this->addSuccess('JWT id_token signature validation success');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadExpiry(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getToken()->isExpired()) {
            throw new LtiException('JWT id_token is expired');
        }

        return $this->addSuccess('JWT id_token is not expired');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadNonce(LtiMessagePayloadInterface $payload): self
    {
        $nonceValue = $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_NONCE);

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
     * @throws LtiExceptionInterface
     */
    private function validatePayloadDeploymentId(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        if (!$registration->hasDeploymentId($payload->getDeploymentId())) {
            throw new LtiException('JWT id_token deployment_id claim not valid for this registration');
        }

        return $this->addSuccess('JWT id_token deployment_id claim valid for this registration');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validateStateSignature(RegistrationInterface $registration, MessagePayloadInterface $state): self
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
     * @throws LtiExceptionInterface
     */
    private function validateStateExpiry(MessagePayloadInterface $state): self
    {
        if ($state->getToken()->isExpired()) {
            throw new LtiException('JWT OIDC state is expired');
        }

        return $this->addSuccess('JWT OIDC state is not expired');
    }

    protected function addSuccess(string $message): self
    {
        $this->successes[] = $message;

        return $this;
    }

    protected function reset(): self
    {
        $this->successes = [];

        return $this;
    }
}
