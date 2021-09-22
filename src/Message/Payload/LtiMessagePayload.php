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

namespace OAT\Library\Lti1p3Core\Message\Payload;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AcsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingContentItemsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ForUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LisClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\NrpsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\PlatformInstanceClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ProctoringSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ProctoringVerifiedUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Role\Collection\RoleCollection;
use OAT\Library\Lti1p3Core\Role\Factory\RoleFactory;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details
 */
class LtiMessagePayload extends MessagePayload implements LtiMessagePayloadInterface
{
    /**
     * @throws LtiExceptionInterface
     */
    public function getMessageType(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_MESSAGE_TYPE);
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getVersion(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_VERSION);
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getDeploymentId(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_DEPLOYMENT_ID);
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getTargetLinkUri(): string
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_TARGET_LINK_URI);
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getRoles(): array
    {
        return $this->getMandatoryClaim(static::CLAIM_LTI_ROLES);
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getValidatedRoleCollection(): RoleCollection
    {
        $collection = new RoleCollection();

        foreach ($this->getMandatoryClaim(static::CLAIM_LTI_ROLES) as $role) {
            $collection->add(RoleFactory::create($role));
        }

        return $collection;
    }

    public function getResourceLink(): ?ResourceLinkClaim
    {
        return $this->getClaim(ResourceLinkClaim::class);
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
        if (null === $this->getClaim(self::CLAIM_SUB)) {
            return null;
        }

        return new UserIdentity(
            (string)$this->getClaim(self::CLAIM_SUB),
            $this->getClaim(self::CLAIM_USER_NAME),
            $this->getClaim(self::CLAIM_USER_EMAIL),
            $this->getClaim(self::CLAIM_USER_GIVEN_NAME),
            $this->getClaim(self::CLAIM_USER_FAMILY_NAME),
            $this->getClaim(self::CLAIM_USER_MIDDLE_NAME),
            $this->getClaim(self::CLAIM_USER_LOCALE),
            $this->getClaim(self::CLAIM_USER_PICTURE)
        );
    }

    public function getForUser(): ?ForUserClaim
    {
        return $this->getClaim(ForUserClaim::class);
    }

    public function getDeepLinkingSettings(): ?DeepLinkingSettingsClaim
    {
        return $this->getClaim(DeepLinkingSettingsClaim::class);
    }

    public function getDeepLinkingContentItems(): ?DeepLinkingContentItemsClaim
    {
        return $this->getClaim(DeepLinkingContentItemsClaim::class);
    }

    public function getDeepLinkingData(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_DEEP_LINKING_DATA);
    }

    public function getDeepLinkingMessage(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_DEEP_LINKING_MESSAGE);
    }

    public function getDeepLinkingLog(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_DEEP_LINKING_LOG);
    }

    public function getDeepLinkingErrorMessage(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_DEEP_LINKING_ERROR_MESSAGE);
    }

    public function getDeepLinkingErrorLog(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_DEEP_LINKING_ERROR_LOG);
    }

    public function getProctoringStartAssessmentUrl(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL);
    }

    public function getProctoringSettings(): ?ProctoringSettingsClaim
    {
        return $this->getClaim(ProctoringSettingsClaim::class);
    }

    public function getProctoringSessionData(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_PROCTORING_SESSION_DATA);
    }

    public function getProctoringAttemptNumber(): ?int
    {
        if ($this->hasClaim(static::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER)) {
            return (int)$this->getClaim(static::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER);
        }

        return null;
    }

    public function getProctoringVerifiedUser(): ?ProctoringVerifiedUserClaim
    {
        return $this->getClaim(ProctoringVerifiedUserClaim::class);
    }

    public function getProctoringEndAssessmentReturn(): bool
    {
        if ($this->hasClaim(static::CLAIM_LTI_PROCTORING_END_ASSESSMENT_RETURN)) {
            return (bool)$this->getClaim(static::CLAIM_LTI_PROCTORING_END_ASSESSMENT_RETURN);
        }

        return false;
    }

    public function getProctoringErrorMessage(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_PROCTORING_ERROR_MESSAGE);
    }

    public function getProctoringErrorLog(): ?string
    {
        return $this->getClaim(static::CLAIM_LTI_PROCTORING_ERROR_LOG);
    }

    public function getAcs(): ?AcsClaim
    {
        return $this->getClaim(AcsClaim::class);
    }

    public function getAgs(): ?AgsClaim
    {
        return $this->getClaim(AgsClaim::class);
    }

    public function getNrps(): ?NrpsClaim
    {
        return $this->getClaim(NrpsClaim::class);
    }

    public function getBasicOutcome(): ?BasicOutcomeClaim
    {
        return $this->getClaim(BasicOutcomeClaim::class);
    }
}
