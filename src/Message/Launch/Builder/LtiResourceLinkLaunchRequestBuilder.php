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

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Content\LtiResourceLinkInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\Token\Builder\MessageTokenBuilder;
use OAT\Library\Lti1p3Core\Message\Token\Builder\MessageTokenBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Token\Claim\MessageTokenClaimInterface;
use OAT\Library\Lti1p3Core\Message\Token\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
 */
class LtiResourceLinkLaunchRequestBuilder
{
    /** @var MessageTokenBuilderInterface */
    private $builder;

    public function __construct(MessageTokenBuilderInterface $builder = null)
    {
        $this->builder = $builder ?? new MessageTokenBuilder();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function buildResourceLinkLaunchRequest(
        LtiResourceLinkInterface $ltiResourceLink,
        RegistrationInterface $registration,
        string $loginHint,
        string $deploymentId = null,
        array $roles = [],
        array $optionalClaims = []
    ): LtiMessageInterface {
        try {
            $resourceLinkClaim = ResourceLinkClaim::denormalize([
                'id' => $ltiResourceLink->getIdentifier(),
                'title' => $ltiResourceLink->getTitle(),
                'description' => $ltiResourceLink->getText(),
            ]);

            $deploymentId = $this->resolveDeploymentId($registration, $deploymentId);

            $targetLinkUri = $ltiResourceLink->getUrl() ?? $registration->getTool()->getLaunchUrl();

            $this->builder
                ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_MESSAGE_TYPE, LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST)
                ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_DEPLOYMENT_ID, $deploymentId)
                ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_TARGET_LINK_URI, $targetLinkUri)
                ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_ROLES, $roles)
                ->withClaim(LtiMessageTokenInterface::CLAIM_REGISTRATION_ID, $registration->getIdentifier())
                ->withClaim($resourceLinkClaim);

            foreach ($optionalClaims as $claimName => $claim) {
                if ($claim instanceof MessageTokenClaimInterface) {
                    $this->builder->withClaim($claim);
                } else {
                    $this->builder->withClaim($claimName, $claim);
                }
            }

            $ltiMessageHintToken = $this->builder
                ->buildMessageToken($registration->getPlatformKeyChain())
                ->getToken();

            return new LtiMessage(
                $registration->getTool()->getOidcLoginInitiationUrl(),
                [
                    'iss' => $registration->getPlatform()->getAudience(),
                    'login_hint' => $loginHint,
                    'target_link_uri' => $targetLinkUri,
                    'lti_message_hint' => $ltiMessageHintToken->__toString(),
                    'lti_deployment_id' => $deploymentId,
                    'client_id' => $registration->getClientId(),
                ]
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create LTI launch request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
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