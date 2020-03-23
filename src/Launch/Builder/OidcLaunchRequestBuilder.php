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
use OAT\Library\Lti1p3Core\Launch\Request\OidcLaunchRequest;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Message\Claim\MessageClaimInterface;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
 */
class OidcLaunchRequestBuilder
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
    public function buildResourceLinkOidcLaunchRequest(
        ResourceLinkInterface $resourceLink,
        DeploymentInterface $deployment,
        string $loginHint,
        array $roles = [],
        array $optionalClaims = []
    ): OidcLaunchRequest {
        try {
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

            foreach ($optionalClaims as $claimName => $claim) {
                if ($claim instanceof MessageClaimInterface) {
                    $this->messageBuilder->withClaim($claim);
                } else {
                    $this->messageBuilder->withClaim($claimName, $claim);
                }
            }

            return new OidcLaunchRequest($deployment->getTool()->getOidcLoginInitiationUrl(), [
                'iss' => $deployment->getPlatform()->getAudience(),
                'login_hint' => $loginHint,
                'target_link_uri' => $targetLinkUri,
                'lti_message_hint' => $this->messageBuilder->getMessage($deployment->getPlatformKeyChain())->getToken()->__toString(),
                'lti_deployment_id' => $deployment->getIdentifier(),
                'client_id' => $deployment->getClientId(),
            ]);

        } catch (LtiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create OIDC launch request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
