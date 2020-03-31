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

use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Claim\LisClaim;
use OAT\Library\Lti1p3Core\Message\Claim\PlatformInstanceClaim;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details
 */
interface LtiMessageInterface extends MessageInterface
{
    // LTI version
    public const LTI_VERSION = '1.3.0';

    // LTI claims
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

    // LTI AGS claim
    public const CLAIM_LTI_AGS = 'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';

    public function getMessageType(): string;

    public function getVersion(): string;

    public function getDeploymentId(): string;

    public function getTargetLinkUri(): string;

    public function getRoles(): array;

    public function getRoleScopeMentor(): array;

    public function getCustom(): array;

    public function getResourceLink(): ResourceLinkClaim;

    public function getContext(): ?ContextClaim;

    public function getPlatformInstance(): ?PlatformInstanceClaim;

    public function getLaunchPresentation(): ?LaunchPresentationClaim;

    public function getLis(): ?LisClaim;

    public function getUserIdentity(): ?UserIdentityInterface;
}
