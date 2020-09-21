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

namespace OAT\Library\Lti1p3Core\DeepLink\Launch\Validator;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Launch\Validator\AbstractLaunchRequestValidator;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation
 */
class DeepLinkingRequestValidator extends AbstractLaunchRequestValidator
{
    /**
     * @throws LtiExceptionInterface
     */
    protected function validateSpecifics(
        RegistrationInterface $registration,
        LtiMessagePayloadInterface $payload,
        MessagePayloadInterface $state
    ): void {
        $this
            ->validatePayloadMessageTypeDeepLinkingRequest($payload)
            ->validatePayloadDeepLinkingSettings($payload);
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadMessageTypeDeepLinkingRequest(LtiMessagePayloadInterface $payload): self
    {
        if ($payload->getMessageType() !== LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST) {
            throw new LtiException(
                sprintf(
                    'JWT id_token message_type must be %s',
                    LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST
                )
            );
        }

        $this->addSuccess(
            sprintf('JWT id_token message_type equals %s', LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST)
        );
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function validatePayloadDeepLinkingSettings(LtiMessagePayloadInterface $payload): self
    {
        if (null === $payload->getDeepLinkingSettings()) {
            throw new LtiException('JWT id_token deep_linking_settings id claim is missing');
        }

        return $this->addSuccess('JWT id_token deep_linking_settings id claim is provided');
    }
}