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

use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details
 */
interface LtiMessageInterface
{
    // LTI version
    public const LTI_VERSION = '1.3.0';

    // LTI message types
    public const LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST = 'LtiResourceLinkRequest';
    public const LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST = 'LtiDeepLinkingRequest';
    public const LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE = 'LtiDeepLinkingResponse';
    public const LTI_MESSAGE_TYPE_START_PROCTORING = 'LtiStartProctoring';
    public const LTI_MESSAGE_TYPE_START_ASSESSMENT = 'LtiStartAssessment';
    public const LTI_MESSAGE_TYPE_END_ASSESSMENT = 'LtiEndAssessment';
    public const LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST = 'LtiSubmissionReviewRequest';

    public function getUrl(): string;

    public function getParameters(): CollectionInterface;

    public static function fromServerRequest(ServerRequestInterface $request): LtiMessageInterface;

    public function toUrl(): string;

    public function toHtmlLink(string $title, array $attributes = []): string;

    public function toHtmlRedirectForm(bool $autoSubmit = true): string;
}
