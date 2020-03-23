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

use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Message\Claim\MessageClaimInterface;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;
use Throwable;

class LtiLaunchRequestBuilder
{
    /** @var MessageBuilder */
    private $messageBuilder;

    public function __construct(MessageBuilder $messageBuilder = null)
    {
        $this->messageBuilder = $messageBuilder ?? new MessageBuilder();
    }

    /**
     * @throws LtiException
     */
    public function buildUserResourceLinkLtiLaunchRequest(
        ResourceLinkInterface $resourceLink,
        DeploymentInterface $deployment,
        UserIdentityInterface $userIdentity,
        array $roles = [],
        array $optionalClaims = [],
        string $state = null
    ): LtiLaunchRequest {

        return $this->buildResourceLinkLtiLaunchRequest(
            $resourceLink,
            $deployment,
            $roles,
            array_merge($optionalClaims, $userIdentity->normalize()),
            $state
        );
    }

    /**
     * @throws LtiException
     */
    public function buildResourceLinkLtiLaunchRequest(
        ResourceLinkInterface $resourceLink,
        DeploymentInterface $deployment,
        array $roles = [],
        array $optionalClaims = [],
        string $state = null
    ): LtiLaunchRequest {

        $resourceLinkClaim = ResourceLinkClaim::denormalize([
            'id' => $resourceLink->getIdentifier(),
            'title' => $resourceLink->getTitle(),
            'description' => $resourceLink->getDescription(),
        ]);

        $targetLinkUri = $resourceLink->getUrl() ?? $deployment->getTool()->getLaunchUrl();

        $this->messageBuilder
            ->withClaim(LtiMessageInterface::CLAIM_LTI_MESSAGE_TYPE, $resourceLink->getType())
            ->withClaim(LtiMessageInterface::CLAIM_LTI_DEPLOYMENT_ID, $deployment->getIdentifier())
            ->withClaim(LtiMessageInterface::CLAIM_LTI_TARGET_LINK_URI, $targetLinkUri)
            ->withClaim(LtiMessageInterface::CLAIM_LTI_ROLES, $roles)
            ->withClaim($resourceLinkClaim);

        return $this->buildLtiLaunchRequest($deployment, $targetLinkUri, $optionalClaims, $state);
    }

    /**
     * @throws LtiException
     */
    protected function buildLtiLaunchRequest(
        DeploymentInterface $deployment,
        string $url,
        array $optionalClaims = [],
        string $state = null
    ): LtiLaunchRequest {
        try {
            foreach ($optionalClaims as $claimName => $claim) {
                if ($claim instanceof MessageClaimInterface) {
                    $this->messageBuilder->withClaim($claim);
                } else {
                    $this->messageBuilder->withClaim($claimName, $claim);
                }
            }

            return new LtiLaunchRequest(
                $url,
                $this->messageBuilder->getLtiMessage($deployment->getPlatformKeyChain())->getToken()->__toString(),
                $state
            );

        } catch (LtiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create LTI launch request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
