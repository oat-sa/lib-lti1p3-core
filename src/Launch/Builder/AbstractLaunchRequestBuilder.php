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

namespace OAT\Library\Lti1p3Core\Launch\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\MessagePayloadClaimInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
 */
abstract class AbstractLaunchRequestBuilder
{
    /** @var MessagePayloadBuilderInterface */
    protected $builder;

    public function __construct(MessagePayloadBuilderInterface $builder = null)
    {
        $this->builder = $builder ?? new MessagePayloadBuilder();
    }

    /**
     * @throws LtiExceptionInterface
     */
    protected function buildLaunchRequest(
        RegistrationInterface $registration,
        string $messageType,
        string $targetLinkUri,
        string $loginHint,
        string $deploymentId = null,
        array $roles = [],
        array $optionalClaims = []
    ): LtiMessageInterface {
        $deploymentId = $this->resolveDeploymentId($registration, $deploymentId);

        $this->builder
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_VERSION, LtiMessageInterface::LTI_VERSION)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE, $messageType)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID, $deploymentId)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_TARGET_LINK_URI, $targetLinkUri)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLES, $roles)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_REGISTRATION_ID, $registration->getIdentifier());

        foreach ($optionalClaims as $claimName => $claim) {
            if ($claim instanceof MessagePayloadClaimInterface) {
                $this->builder->withClaim($claim);
            } else {
                $this->builder->withClaim($claimName, $claim);
            }
        }

        $ltiMessageHintPayload = $this->builder->buildMessagePayload($registration->getPlatformKeyChain());

        return new LtiMessage(
            $registration->getTool()->getOidcInitiationUrl(),
            [
                'iss' => $registration->getPlatform()->getAudience(),
                'login_hint' => $loginHint,
                'target_link_uri' => $targetLinkUri,
                'lti_message_hint' => $ltiMessageHintPayload->getToken()->__toString(),
                'lti_deployment_id' => $deploymentId,
                'client_id' => $registration->getClientId(),
            ]
        );
    }

    /**
     * @throws LtiExceptionInterface
     */
    protected function resolveDeploymentId(RegistrationInterface $registration, string $deploymentId = null): string
    {
        if (null !== $deploymentId) {
            if (!$registration->hasDeploymentId($deploymentId)) {
                throw new LtiException(sprintf(
                    'Invalid deployment id %s for registration %s',
                    $deploymentId,
                    $registration->getIdentifier()
                ));
            }
        } else {
            $deploymentId = $registration->getDefaultDeploymentId();

            if (null === $deploymentId) {
                throw new LtiException('Mandatory deployment id is missing');
            }
        }

        return $deploymentId;
    }
}