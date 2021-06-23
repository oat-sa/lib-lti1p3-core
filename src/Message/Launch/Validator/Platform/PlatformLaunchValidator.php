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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\AbstractLaunchValidator;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResult;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResultInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation-0
 */
class PlatformLaunchValidator extends AbstractLaunchValidator implements PlatformLaunchValidatorInterface
{
    public function getSupportedMessageTypes(): array
    {
        return [
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
        ];
    }

    public function validateToolOriginatingLaunch(ServerRequestInterface $request): LaunchValidationResultInterface
    {
        $this->reset();

        try {
            $message = LtiMessage::fromServerRequest($request);

            $payload = new LtiMessagePayload($this->parser->parse($message->getParameters()->getMandatory('JWT')));

            $audiences = $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_AUD);
            $audiences = is_array($audiences) ? $audiences : [$audiences];

            $registration = null;

            foreach ($audiences as $audience) {
                $registration = $this->registrationRepository->findByPlatformIssuer(
                    $audience,
                    $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_ISS)
                );

                if (null !== $registration) {
                    break;
                }
            }

            if (null === $registration) {
                throw new LtiException('No matching registration found platform side');
            }

            $this
                ->validatePayloadKid($payload)
                ->validatePayload($registration, $payload)
                ->validatePayloadVersion($payload)
                ->validatePayloadMessageType($payload)
                ->validatePayloadNonce($payload)
                ->validatePayloadDeploymentId($registration, $payload)
                ->validatePayloadLaunchMessageTypeSpecifics($registration, $payload);

            return new LaunchValidationResult($registration, $payload, null, $this->successes);

        } catch (Throwable $exception) {
            return new LaunchValidationResult(null, null, null, $this->successes, $exception->getMessage());
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadKid(LtiMessagePayloadInterface $payload): self
    {
        if (!$payload->getToken()->getHeaders()->has(LtiMessagePayloadInterface::HEADER_KID)) {
            throw new LtiException('JWT kid header is missing');
        }

        $this->addSuccess('JWT kid header is provided');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadVersion(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getVersion() !== LtiMessageInterface::LTI_VERSION) {
            throw new LtiException('JWT version claim is invalid');
        }

        $this->addSuccess('JWT version claim is valid');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadMessageType(LtiMessagePayloadInterface $payload): self
    {
        if (
        !in_array(
            $payload->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE),
            $this->getSupportedMessageTypes()
        )
        ) {
            throw new LtiException('JWT message_type claim is not supported');
        }

        $this->addSuccess('JWT message_type claim is valid');

        return $this;
    }


    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayload(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        $toolKeyChain = $registration->getToolKeyChain();

        $key = $toolKeyChain
            ? $toolKeyChain->getPublicKey()
            : $this->fetcher->fetchKey(
                $registration->getToolJwksUrl(),
                $payload->getToken()->getHeaders()->getMandatory(LtiMessagePayloadInterface::HEADER_KID)
            );

        if (!$this->validator->validate($payload->getToken(), $key)) {
            throw new LtiException('JWT validation failure');
        }

        $this->addSuccess('JWT validation success');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadNonce(LtiMessagePayloadInterface $payload): self
    {
        if (empty($payload->getToken()->getClaims()->get(LtiMessagePayloadInterface::CLAIM_NONCE))) {
            throw new LtiException('JWT nonce claim is missing');
        }

        $nonceValue = $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_NONCE);

        $nonce = $this->nonceRepository->find($nonceValue);

        if (null !== $nonce) {
            if (!$nonce->isExpired()) {
                throw new LtiException('JWT nonce claim already used');
            }
        } else {
            $this->nonceRepository->save(
                new Nonce($nonceValue, Carbon::now()->addSeconds(NonceGeneratorInterface::TTL))
            );
        }

        $this->addSuccess('JWT nonce claim is valid');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadDeploymentId(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        if (!$payload->hasClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID)) {
            throw new LtiException('JWT deployment_id claim is missing');
        }

        if (!$registration->hasDeploymentId($payload->getDeploymentId())) {
            throw new LtiException('JWT deployment_id claim not valid for this registration');
        }

        $this->addSuccess('JWT deployment_id claim valid for this registration');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadLaunchMessageTypeSpecifics(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE) {
            if (empty($payload->getDeepLinkingData())) {
                throw new LtiException('JWT data deep linking claim is missing');
            }

            $dataToken = $this->parser->parse($payload->getDeepLinkingData());

            $platformKeyChain = $registration->getPlatformKeyChain();

            if (null === $platformKeyChain) {
                throw new LtiException('JWT data deep linking claim validation failure: platform key chain is not configured');
            }

            if (!$this->validator->validate($dataToken, $platformKeyChain->getPublicKey())) {
                throw new LtiException('JWT data deep linking claim validation failure');
            }
        }

        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT) {
            if (empty($payload->getProctoringSessionData())) {
                throw new LtiException('JWT session_data proctoring claim is missing');
            }

            $dataToken = $this->parser->parse($payload->getProctoringSessionData());

            $platformKeyChain = $registration->getPlatformKeyChain();

            if (null === $platformKeyChain) {
                throw new LtiException('JWT session_data proctoring claim validation failure: platform key chain is not configured');
            }

            if (!$this->validator->validate($dataToken, $platformKeyChain->getPublicKey())) {
                throw new LtiException('JWT session_data proctoring claim validation failure');
            }

            if (empty($payload->getProctoringAttemptNumber())) {
                throw new LtiException('JWT attempt_number proctoring claim is invalid');
            }

            $resourceLink = $payload->getResourceLink();

            if (null === $resourceLink) {
                throw new LtiException('JWT resource_link claim is missing');
            }

            if (empty($resourceLink->getIdentifier())) {
                throw new LtiException('JWT resource_link id claim is invalid');
            }
        }

        $this->addSuccess(
            sprintf('JWT message type claim %s requirements are valid', $payload->getMessageType())
        );

        return $this;
    }
}
