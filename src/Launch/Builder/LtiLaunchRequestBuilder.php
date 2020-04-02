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

use Lcobucci\JWT\Claim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Message\Claim\MessageClaimInterface;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;
use Throwable;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3#launch-from-a-resource-link-0
 */
class LtiLaunchRequestBuilder
{
    /** @var MessageBuilder */
    private $messageBuilder;

    public function __construct(MessageBuilder $messageBuilder = null)
    {
        $this->messageBuilder = $messageBuilder ?? new MessageBuilder();
    }

    public function copyFromMessage(MessageInterface $message): self
    {
        /** @var Claim $claim */
        foreach ($message->getToken()->getClaims() as $claim) {
            $this->messageBuilder->withClaim($claim->getName(), $claim->getValue());
        }

        return $this;
    }

    /**
     * @throws LtiException
     */
    public function buildUserResourceLinkLtiLaunchRequest(
        ResourceLinkInterface $resourceLink,
        RegistrationInterface $registration,
        UserIdentityInterface $userIdentity,
        string $deploymentId = null,
        array $roles = [],
        array $optionalClaims = [],
        string $state = null
    ): LtiLaunchRequest {

        return $this->buildResourceLinkLtiLaunchRequest(
            $resourceLink,
            $registration,
            $deploymentId,
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
        RegistrationInterface $registration,
        string $deploymentId = null,
        array $roles = [],
        array $optionalClaims = [],
        string $state = null
    ): LtiLaunchRequest {

        $resourceLinkClaim = ResourceLinkClaim::denormalize([
            'id' => $resourceLink->getIdentifier(),
            'title' => $resourceLink->getTitle(),
            'description' => $resourceLink->getDescription(),
        ]);

        $targetLinkUri = $resourceLink->getUrl() ?? $registration->getTool()->getLaunchUrl();

        $this->messageBuilder
            ->withClaim(LtiMessageInterface::CLAIM_LTI_MESSAGE_TYPE, $resourceLink->getType())
            ->withClaim(LtiMessageInterface::CLAIM_LTI_TARGET_LINK_URI, $targetLinkUri)
            ->withClaim(LtiMessageInterface::CLAIM_LTI_ROLES, $roles)
            ->withClaim($resourceLinkClaim);

        return $this->buildLtiLaunchRequest($registration, $targetLinkUri, $deploymentId, $optionalClaims, $state);
    }

    /**
     * @throws LtiException
     */
    protected function buildLtiLaunchRequest(
        RegistrationInterface $registration,
        string $url,
        string $deploymentId = null,
        array $optionalClaims = [],
        string $state = null
    ): LtiLaunchRequest {
        try {
            if (null !== $deploymentId) {
                if (!$registration->hasDeploymentId($deploymentId)) {
                    throw new LtiException(sprintf(
                        'invalid deployment id %s for registration %s',
                        $deploymentId,
                        $registration->getIdentifier()
                    ));
                }
            } else {
                $deploymentId = $registration->getDefaultDeploymentId();

                if (null === $deploymentId) {
                    throw new LtiException('mandatory deployment id is missing');
                }
            }

            $this->messageBuilder
                ->withClaim(LtiMessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
                ->withClaim(LtiMessageInterface::CLAIM_AUD, $registration->getClientId())
                ->withClaim(LtiMessageInterface::CLAIM_LTI_DEPLOYMENT_ID, $deploymentId);

            foreach ($optionalClaims as $claimName => $claim) {
                if ($claim instanceof MessageClaimInterface) {
                    $this->messageBuilder->withClaim($claim);
                } else {
                    $this->messageBuilder->withClaim($claimName, $claim);
                }
            }

            $idToken = $this->messageBuilder
                ->getLtiMessage($registration->getPlatformKeyChain())
                ->getToken();

            return new LtiLaunchRequest($url, [
                'id_token' => $idToken->__toString(),
                'state' => $state
            ]);

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
