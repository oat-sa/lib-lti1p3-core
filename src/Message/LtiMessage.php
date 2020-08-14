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

namespace OAT\Library\Lti1p3Core\Message;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Claim\LisClaim;
use OAT\Library\Lti1p3Core\Message\Claim\NrpsClaim;
use OAT\Library\Lti1p3Core\Message\Claim\PlatformInstanceClaim;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details
 */
class LtiMessage extends Message implements LtiMessageInterface
{
    /**
     * @throws LtiException
     */
    public function getMessageType(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_MESSAGE_TYPE);
    }

    /**
     * @throws LtiException
     */
    public function getVersion(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_VERSION);
    }

    /**
     * @throws LtiException
     */
    public function getDeploymentId(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_DEPLOYMENT_ID);
    }

    /**
     * @throws LtiException
     */
    public function getTargetLinkUri(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_TARGET_LINK_URI);
    }

    /**
     * @throws LtiException
     */
    public function getResourceLink(): ResourceLinkClaim
    {
        return $this->getMandatoryClaim(ResourceLinkClaim::class);
    }

    /**
     * @throws LtiException
     */
    public function getRoles(): array
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_ROLES);
    }

    public function getRoleScopeMentor(): array
    {
        return $this->getClaim(static::CLAIM_LTI_ROLE_SCOPE_MENTOR, []);
    }

    public function getCustom(): array
    {
        return $this->getClaim(static::CLAIM_LTI_CUSTOM, []);
    }

    public function getContext(): ?ContextClaim
    {
        return $this->getClaim(ContextClaim::class);
    }

    public function getPlatformInstance(): ?PlatformInstanceClaim
    {
        return $this->getClaim(PlatformInstanceClaim::class);
    }

    public function getLaunchPresentation(): ?LaunchPresentationClaim
    {
        return $this->getClaim(LaunchPresentationClaim::class);
    }

    public function getLis(): ?LisClaim
    {
        return $this->getClaim(LisClaim::class);
    }

    public function getUserIdentity(): ?UserIdentityInterface
    {
        if (null === $this->getClaim('sub')) {
            return null;
        }

        return new UserIdentity(
            (string)$this->getClaim('sub'),
            $this->getClaim('name'),
            $this->getClaim('email'),
            $this->getClaim('given_name'),
            $this->getClaim('family_name'),
            $this->getClaim('middle_name'),
            $this->getClaim('locale'),
            $this->getClaim('picture')
        );
    }

    public function getAgs(): ?AgsClaim
    {
        return $this->getClaim(AgsClaim::class);
    }

    public function getNrps(): ?NrpsClaim
    {
        return $this->getClaim(NrpsClaim::class);
    }
}
