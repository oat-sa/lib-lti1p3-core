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
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResult;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation
 */
class ToolLaunchValidator extends AbstractLaunchValidator
{
    public function getSupportedMessageTypes(): array
    {
        return [
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
            LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
        ];
    }

    public function validatePlatformOriginatingLaunch(ServerRequestInterface $request): LaunchValidationResult
    {
        $this->reset();

        try {
            $message = LtiMessage::fromServerRequest($request);

            $payload = new LtiMessagePayload($this->parser->parse($message->getMandatoryParameter('id_token')));
            $state = new MessagePayload($this->parser->parse($message->getMandatoryParameter('state')));

            $registration = $this->registrationRepository->findByPlatformIssuer(
                $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_ISS),
                $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_AUD)
            );

            if (null === $registration) {
                throw new LtiException('No matching registration found tool side');
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
                ->validatePayloadLaunchMessageTypeSpecifics($payload)
                ->validateStateExpiry($state)
                ->validateStateSignature($registration, $state);

            return new LaunchValidationResult($registration, $payload, $state, $this->successes);

        } catch (Throwable $exception) {
            return new LaunchValidationResult(null, null, null, $this->successes, $exception->getMessage());
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadKid(LtiMessagePayloadInterface $payload): self
    {
        if (!$payload->getToken()->hasHeader(LtiMessagePayloadInterface::HEADER_KID)) {
            throw new LtiException('ID token kid header is missing');
        }

        return $this->addSuccess('ID token kid header is provided');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadVersion(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getVersion() !== LtiMessageInterface::LTI_VERSION) {
            throw new LtiException('ID token version claim is invalid');
        }

        return $this->addSuccess('ID token version claim is valid');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadMessageType(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getMessageType() === '') {
            throw new LtiException('ID token message_type claim is missing');
        }

        if (!in_array($payload->getMessageType(), $this->getSupportedMessageTypes())) {
            throw new LtiException(
                sprintf('ID token message_type claim %s is not supported', $payload->getMessageType())
            );
        }

        return $this->addSuccess('ID token message_type claim is valid');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadRoles(LtiMessagePayloadInterface $payload): self
    {
        if (!is_array($payload->getToken()->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLES))) {
            throw new LtiException('ID token roles claim is invalid');
        }

        return $this->addSuccess('ID token roles claim is valid');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadUserIdentifier(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getUserIdentity() && $payload->getUserIdentity()->getIdentifier() === '') {
            throw new LtiException('JID token user identifier (sub) claim is invalid');
        }

        return $this->addSuccess('ID token user identifier (sub) claim is valid');
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
            throw new LtiException('ID token signature validation failure');
        }

        return $this->addSuccess('ID token signature validation success');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadExpiry(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getToken()->isExpired()) {
            throw new LtiException('ID token is expired');
        }

        return $this->addSuccess('ID token is not expired');
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
                throw new LtiException('ID token nonce claim already used');
            }

            return $this->addSuccess('ID token nonce claim already used but expired');
        } else {
            $this->nonceRepository->save(
                new Nonce($nonceValue, Carbon::now()->addSeconds(NonceGeneratorInterface::TTL))
            );

            return $this->addSuccess('ID token nonce claim is valid');
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadDeploymentId(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        if (!$registration->hasDeploymentId($payload->getDeploymentId())) {
            throw new LtiException('ID token deployment_id claim not valid for this registration');
        }

        return $this->addSuccess('ID token deployment_id claim valid for this registration');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadLaunchMessageTypeSpecifics(LtiMessagePayloadInterface $payload): self
    {
        switch ($payload->getMessageType()) {
            case LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST:
                if ($payload->getResourceLink()->getIdentifier() === '') {
                    throw new LtiException('ID token resource_link id claim is invalid');
                }

                return $this->addSuccess('ID token resource_link id claim is valid');

            case LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST:
                if (null === $payload->getDeepLinkingSettings()) {
                    throw new LtiException('ID token deep_linking_settings id claim is invalid');
                }

                return $this->addSuccess('ID token deep_linking_settings id claim is valid');

            case LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING:
                if (null === $payload->getProctoringStartAssessmentUrl()) {
                    throw new LtiException('ID token start_assessment_url proctoring claim is invalid');
                }

                $this->addSuccess('ID token start_assessment_url claim proctoring is valid');

                if (null === $payload->getProctoringSessionData()) {
                    throw new LtiException('ID token session_data proctoring claim is invalid');
                }

                $this->addSuccess('ID token session_data proctoring claim is valid');

                if (null === $payload->getProctoringAttemptNumber()) {
                    throw new LtiException('ID token attempt_number proctoring claim is invalid');
                }

                $this->addSuccess('ID token attempt_number proctoring claim is valid');

                if (null === $payload->getLegacyUserIdentifier()) {
                    throw new LtiException('ID token lti11_legacy_user_id claim is invalid');
                }

                return $this->addSuccess('ID token lti11_legacy_user_id claim is valid');

            default:
                throw new LtiException(sprintf('Launch message type %s not handled', $payload->getMessageType()));
        }
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
            throw new LtiException('State signature validation failure');
        }

        return $this->addSuccess('State signature validation success');
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validateStateExpiry(MessagePayloadInterface $state): self
    {
        if ($state->getToken()->isExpired()) {
            throw new LtiException('State is expired');
        }

        return $this->addSuccess('State is not expired');
    }
}
