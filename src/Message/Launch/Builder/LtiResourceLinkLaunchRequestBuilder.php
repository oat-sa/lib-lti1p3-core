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
use OAT\Library\Lti1p3Core\Resource\ResourceLink\LtiResourceLinkInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
 */
class LtiResourceLinkLaunchRequestBuilder extends AbstractLaunchRequestBuilder
{
    /**
     * @throws LtiExceptionInterface
     */
    public function build(
        LtiResourceLinkInterface $ltiResourceLink,
        RegistrationInterface $registration,
        string $loginHint,
        string $deploymentId = null,
        array $roles = [],
        array $optionalClaims = []
    ): LtiMessageInterface {
        try {
            $this->builder->withClaim(
                ResourceLinkClaim::denormalize([
                    'id' => $ltiResourceLink->getIdentifier(),
                    'title' => $ltiResourceLink->getTitle(),
                    'description' => $ltiResourceLink->getText(),
                ])
            );

            return $this->buildLaunchRequest(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                $ltiResourceLink->getUrl() ?? $registration->getTool()->getLaunchUrl(),
                $loginHint,
                $deploymentId,
                $roles,
                $optionalClaims
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
}
