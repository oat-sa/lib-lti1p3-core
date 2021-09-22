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

namespace OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool;

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
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation
 */
class ToolLaunchValidator extends AbstractLaunchValidator implements ToolLaunchValidatorInterface
{
    public function getSupportedMessageTypes(): array
    {
        return [
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
            LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
            LtiMessageInterface::LTI_MESSAGE_TYPE_END_ASSESSMENT,
            LtiMessageInterface::LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST,
        ];
    }

    public function validatePlatformOriginatingLaunch(ServerRequestInterface $request): LaunchValidationResultInterface
    {
        $this->reset();

        try {
            $message = LtiMessage::fromServerRequest($request);

            $payload = new LtiMessagePayload($this->parser->parse($message->getParameters()->getMandatory('id_token')));
            $state = new MessagePayload($this->parser->parse($message->getParameters()->getMandatory('state')));

            $audiences = $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_AUD);
            $audiences = is_array($audiences) ? $audiences : [$audiences];

            $registration = null;

            foreach ($audiences as $audience) {
                $registration = $this->registrationRepository->findByPlatformIssuer(
                    $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_ISS),
                    $audience
                );
            }

            if (null === $registration) {
                throw new LtiException('No matching registration found tool side');
            }

            $this
                ->validatePayloadKid($payload)
                ->validatePayloadToken($registration, $payload)
                ->validatePayloadVersion($payload)
                ->validatePayloadMessageType($payload)
                ->validatePayloadRoles($payload)
                ->validatePayloadUserIdentifier($payload)
                ->validatePayloadNonce($payload)
                ->validatePayloadDeploymentId($registration, $payload)
                ->validatePayloadLaunchMessageTypeSpecifics($payload)
                ->validateStateToken($registration, $state);

            return new LaunchValidationResult($registration, $payload, $state, $this->successes);

        } catch (Throwable $exception) {
            return new LaunchValidationResult(null, null, null, $this->successes, $exception->getMessage());
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadToken(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        $platformKeyChain = $registration->getPlatformKeyChain();

        $key = $platformKeyChain
            ? $platformKeyChain->getPublicKey()
            : $this->fetcher->fetchKey(
                $registration->getPlatformJwksUrl(),
                $payload->getToken()->getHeaders()->getMandatory(LtiMessagePayloadInterface::HEADER_KID)
            );

        if (!$this->validator->validate($payload->getToken(), $key)) {
            throw new LtiException('ID token validation failure');
        }

        $this->addSuccess('ID token validation success');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadKid(LtiMessagePayloadInterface $payload): self
    {
        if (!$payload->getToken()->getHeaders()->has(LtiMessagePayloadInterface::HEADER_KID)) {
            throw new LtiException('ID token kid header is missing');
        }

        $this->addSuccess('ID token kid header is provided');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadVersion(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_VERSION) !== LtiMessageInterface::LTI_VERSION) {
            throw new LtiException('ID token version claim is invalid');
        }

        $this->addSuccess('ID token version claim is valid');

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
            throw new LtiException('ID token message_type claim is not supported');
        }

        $this->addSuccess('ID token message_type claim is valid');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadRoles(LtiMessagePayloadInterface $payload): self
    {
        if (!is_array($payload->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLES))) {
            throw new LtiException('ID token roles claim is invalid');
        }

        $this->addSuccess('ID token roles claim is valid');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadUserIdentifier(LtiMessagePayloadInterface $payload): self
    {
        if (
            $payload->hasClaim(LtiMessagePayloadInterface::CLAIM_SUB)
            && empty($payload->getClaim(LtiMessagePayloadInterface::CLAIM_SUB))
        ) {
            throw new LtiException('ID token user identifier (sub) claim is invalid');
        }

        $this->addSuccess('ID token user identifier (sub) claim is valid');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadNonce(LtiMessagePayloadInterface $payload): self
    {
        if (empty($payload->getToken()->getClaims()->get(LtiMessagePayloadInterface::CLAIM_NONCE))) {
            throw new LtiException('ID token nonce claim is missing');
        }

        $nonceValue = $payload->getMandatoryClaim(MessagePayloadInterface::CLAIM_NONCE);

        $nonce = $this->nonceRepository->find($nonceValue);

        if (null !== $nonce) {
            if (!$nonce->isExpired()) {
                throw new LtiException('ID token nonce claim already used');
            }
        } else {
            $this->nonceRepository->save(
                new Nonce($nonceValue, Carbon::now()->addSeconds(NonceGeneratorInterface::TTL))
            );
        }

        $this->addSuccess('ID token nonce claim is valid');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadDeploymentId(RegistrationInterface $registration, LtiMessagePayloadInterface $payload): self
    {
        if (!$payload->hasClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID)) {
            throw new LtiException('ID token deployment_id claim is missing');
        }

        if (!$registration->hasDeploymentId($payload->getDeploymentId())) {
            throw new LtiException('ID token deployment_id claim not valid for this registration');
        }

        $this->addSuccess('ID token deployment_id claim valid for this registration');

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadLaunchMessageTypeSpecifics(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST) {

            $resourceLink = $payload->getResourceLink();

            if (null === $resourceLink) {
                throw new LtiException('ID token resource_link claim is missing');
            }

            if (empty($resourceLink->getIdentifier())) {
                throw new LtiException('ID token resource_link id claim is invalid');
            }
        }

        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST) {
            if (empty($payload->getDeepLinkingSettings())) {
                throw new LtiException('ID token deep_linking_settings id claim is invalid');
            }
        }

        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING) {

            if (empty($payload->getProctoringStartAssessmentUrl())) {
                throw new LtiException('ID token start_assessment_url proctoring claim is invalid');
            }

            if (empty($payload->getProctoringSessionData())) {
                throw new LtiException('ID token session_data proctoring claim is invalid');
            }

            if (empty($payload->getProctoringAttemptNumber())) {
                throw new LtiException('ID token attempt_number proctoring claim is invalid');
            }

            $resourceLink = $payload->getResourceLink();

            if (null === $resourceLink) {
                throw new LtiException('ID token resource_link claim is missing');
            }

            if (empty($resourceLink->getIdentifier())) {
                throw new LtiException('ID token resource_link id claim is invalid');
            }
        }

        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_END_ASSESSMENT) {
            if (empty($payload->getProctoringAttemptNumber())) {
                throw new LtiException('ID token attempt_number proctoring claim is invalid');
            }
        }

        if ($payload->getMessageType() === LtiMessageInterface::LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST) {

            $ags = $payload->getAgs();

            if (null === $ags) {
                throw new LtiException('ID token AGS submission review claim is missing');
            }

            if (empty($ags->getLineItemUrl())) {
                throw new LtiException('ID token AGS line item submission review claim is invalid');
            }

            if (empty($payload->getForUser())) {
                throw new LtiException('ID token for_user submission review claim is invalid');
            }
        }

        $this->addSuccess(
            sprintf('ID token message type claim %s requirements are valid', $payload->getMessageType())
        );

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validateStateToken(RegistrationInterface $registration, MessagePayloadInterface $state): self
    {
        $toolKeyChain = $registration->getToolKeyChain();

        if (null === $toolKeyChain) {
            throw new LtiException('State validation failure: tool key chain not configured');
        }

        if (!$this->validator->validate($state->getToken(), $toolKeyChain->getPublicKey())) {
            throw new LtiException('State validation failure');
        }

        $this->addSuccess('State validation success');

        return $this;
    }
}
