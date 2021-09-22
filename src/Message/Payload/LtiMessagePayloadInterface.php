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

namespace OAT\Library\Lti1p3Core\Message\Payload;

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
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details
 */
interface LtiMessagePayloadInterface extends MessagePayloadInterface
{
    // Core claims
    public const CLAIM_LTI_MESSAGE_TYPE = 'https://purl.imsglobal.org/spec/lti/claim/message_type';
    public const CLAIM_LTI_VERSION = 'https://purl.imsglobal.org/spec/lti/claim/version';
    public const CLAIM_LTI_DEPLOYMENT_ID = 'https://purl.imsglobal.org/spec/lti/claim/deployment_id';
    public const CLAIM_LTI_ROLES = 'https://purl.imsglobal.org/spec/lti/claim/roles';
    public const CLAIM_LTI_CONTEXT = 'https://purl.imsglobal.org/spec/lti/claim/context';
    public const CLAIM_LTI_TOOL_PLATFORM = 'https://purl.imsglobal.org/spec/lti/claim/tool_platform';
    public const CLAIM_LTI_ROLE_SCOPE_MENTOR = 'https://purl.imsglobal.org/spec/lti/claim/role_scope_mentor';
    public const CLAIM_LTI_LAUNCH_PRESENTATION = 'https://purl.imsglobal.org/spec/lti/claim/launch_presentation';
    public const CLAIM_LTI_LIS = 'https://purl.imsglobal.org/spec/lti/claim/lis';
    public const CLAIM_LTI_CUSTOM = 'https://purl.imsglobal.org/spec/lti/claim/custom';
    public const CLAIM_LTI_TARGET_LINK_URI = 'https://purl.imsglobal.org/spec/lti/claim/target_link_uri';
    public const CLAIM_LTI_RESOURCE_LINK = 'https://purl.imsglobal.org/spec/lti/claim/resource_link';
    public const CLAIM_LTI_FOR_USER = 'https://purl.imsglobal.org/spec/lti/claim/for_user';

    // Deep Linking claims
    public const CLAIM_LTI_DEEP_LINKING_SETTINGS = 'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings';
    public const CLAIM_LTI_DEEP_LINKING_CONTENT_ITEMS = 'https://purl.imsglobal.org/spec/lti-dl/claim/content_items';
    public const CLAIM_LTI_DEEP_LINKING_DATA = 'https://purl.imsglobal.org/spec/lti-dl/claim/data';
    public const CLAIM_LTI_DEEP_LINKING_MESSAGE = 'https://purl.imsglobal.org/spec/lti-dl/claim/msg';
    public const CLAIM_LTI_DEEP_LINKING_LOG = 'https://purl.imsglobal.org/spec/lti-dl/claim/log';
    public const CLAIM_LTI_DEEP_LINKING_ERROR_MESSAGE = 'https://purl.imsglobal.org/spec/lti-dl/claim/errormsg';
    public const CLAIM_LTI_DEEP_LINKING_ERROR_LOG = 'https://purl.imsglobal.org/spec/lti-dl/claim/errorlog';

    // Proctoring claims
    public const CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL = 'https://purl.imsglobal.org/spec/lti-ap/claim/start_assessment_url';
    public const CLAIM_LTI_PROCTORING_SETTINGS = 'https://purl.imsglobal.org/spec/lti-ap/claim/proctoring_settings';
    public const CLAIM_LTI_PROCTORING_SESSION_DATA = 'https://purl.imsglobal.org/spec/lti-ap/claim/session_data';
    public const CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER = 'https://purl.imsglobal.org/spec/lti-ap/claim/attempt_number';
    public const CLAIM_LTI_PROCTORING_VERIFIED_USER = 'https://purl.imsglobal.org/spec/lti-ap/claim/verified_user';
    public const CLAIM_LTI_PROCTORING_END_ASSESSMENT_RETURN = 'https://purl.imsglobal.org/spec/lti-ap/claim/end_assessment_return';
    public const CLAIM_LTI_PROCTORING_ERROR_MESSAGE = ' https://purl.imsglobal.org/spec/lti-ap/claim/errormsg';
    public const CLAIM_LTI_PROCTORING_ERROR_LOG = ' https://purl.imsglobal.org/spec/lti-ap/claim/errorlog ';

    // ACS claim
    public const CLAIM_LTI_ACS = 'https://purl.imsglobal.org/spec/lti-ap/claim/acs';

    // AGS claim
    public const CLAIM_LTI_AGS = 'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';

    // NRPS claim
    public const CLAIM_LTI_NRPS = 'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';

    // Basic Outcome claim
    public const CLAIM_LTI_BASIC_OUTCOME = 'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome';

    public function getMessageType(): string;

    public function getVersion(): string;

    public function getDeploymentId(): string;

    public function getTargetLinkUri(): string;

    public function getRoles(): array;

    public function getValidatedRoleCollection(): RoleCollection;

    public function getRoleScopeMentor(): array;

    public function getCustom(): array;

    public function getResourceLink(): ?ResourceLinkClaim;

    public function getContext(): ?ContextClaim;

    public function getPlatformInstance(): ?PlatformInstanceClaim;

    public function getLaunchPresentation(): ?LaunchPresentationClaim;

    public function getLis(): ?LisClaim;

    public function getUserIdentity(): ?UserIdentityInterface;

    public function getForUser(): ?ForUserClaim;

    public function getDeepLinkingSettings(): ?DeepLinkingSettingsClaim;

    public function getDeepLinkingContentItems(): ?DeepLinkingContentItemsClaim;

    public function getDeepLinkingData(): ?string;

    public function getDeepLinkingMessage(): ?string;

    public function getDeepLinkingLog(): ?string;

    public function getDeepLinkingErrorMessage(): ?string;

    public function getDeepLinkingErrorLog(): ?string;

    public function getProctoringStartAssessmentUrl(): ?string;

    public function getProctoringSettings(): ?ProctoringSettingsClaim;

    public function getProctoringSessionData(): ?string;

    public function getProctoringAttemptNumber(): ?int;

    public function getProctoringVerifiedUser(): ?ProctoringVerifiedUserClaim;

    public function getProctoringEndAssessmentReturn(): bool;

    public function getProctoringErrorMessage(): ?string;

    public function getProctoringErrorLog(): ?string;

    public function getAcs(): ?AcsClaim;

    public function getAgs(): ?AgsClaim;

    public function getNrps(): ?NrpsClaim;

    public function getBasicOutcome(): ?BasicOutcomeClaim;
}
