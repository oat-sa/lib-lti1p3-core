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

namespace OAT\Library\Lti1p3Core\Message\Launch\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#tool-originating-messages
 */
class ToolOriginatingLaunchBuilder extends AbstractLaunchBuilder
{
    /**
     * @throws LtiExceptionInterface
     */
    public function buildToolOriginatingLaunch(
        RegistrationInterface $registration,
        string $messageType,
        string $platformUrl,
        ?string $deploymentId = null,
        array $optionalClaims = []
    ): LtiMessageInterface {
        $deploymentId = $this->resolveDeploymentId($registration, $deploymentId);

        $this->builder
            ->withClaim(MessagePayloadInterface::CLAIM_ISS, $registration->getClientId())
            ->withClaim(MessagePayloadInterface::CLAIM_AUD, $registration->getPlatform()->getAudience())
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_VERSION, LtiMessageInterface::LTI_VERSION)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE, $messageType)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID, $deploymentId);

        $this->applyOptionalClaims($optionalClaims);

        $payload = $this->builder->buildMessagePayload($registration->getToolKeyChain());

        return new LtiMessage(
            $platformUrl,
            [
                'JWT' => $payload->getToken()->toString(),
            ]
        );
    }
}
