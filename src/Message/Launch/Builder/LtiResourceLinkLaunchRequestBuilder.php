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
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLinkInterface;
use Throwable;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#resource-link-launch-request-message
 */
class LtiResourceLinkLaunchRequestBuilder extends PlatformOriginatingLaunchBuilder
{
    /**
     * @throws LtiExceptionInterface
     */
    public function buildLtiResourceLinkLaunchRequest(
        LtiResourceLinkInterface $ltiResourceLink,
        RegistrationInterface $registration,
        string $loginHint,
        ?string $deploymentId = null,
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

            $launchUrl = $ltiResourceLink->getUrl() ?? $registration->getTool()->getLaunchUrl();

            if (null === $launchUrl) {
                throw new LtiException('Neither resource link url nor tool default url were presented');
            }

            return $this->buildPlatformOriginatingLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                $launchUrl,
                $loginHint,
                $deploymentId,
                $roles,
                $optionalClaims
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create LTI resource link launch request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
