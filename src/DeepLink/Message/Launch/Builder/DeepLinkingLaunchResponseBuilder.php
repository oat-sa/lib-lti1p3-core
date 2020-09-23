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

namespace OAT\Library\Lti1p3Core\DeepLink\Message\Launch\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingContentItemsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Resource\ResourceCollectionInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-message
 */
class DeepLinkingLaunchResponseBuilder extends ToolLaunchBuilder
{
    /**
     * @throws LtiExceptionInterface
     */
    public function buildLaunchResponse(
        ResourceCollectionInterface $resourceCollection,
        RegistrationInterface $registration,
        string $deepLinkingReturnUrl,
        string $deploymentId = null,
        string $deppLinkingData = null,
        string $deppLinkingMessage = null,
        string $deppLinkingLog = null,
        array $optionalClaims = []
    ): LtiMessageInterface {
        try {
            $deppLinkingMessage = $deppLinkingMessage ?? sprintf('%s item(s) provided', $resourceCollection->count());
            $deppLinkingLog = $deppLinkingLog ?? $deppLinkingMessage;

            $this->builder
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA, $deppLinkingData)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_MESSAGE, $deppLinkingMessage)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_LOG, $deppLinkingLog)
                ->withClaim(DeepLinkingContentItemsClaim::fromResourceCollection($resourceCollection));

            return $this->buildToolLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                $deepLinkingReturnUrl,
                $deploymentId,
                $optionalClaims
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create deep linking launch response: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function buildLaunchErrorResponse(
        RegistrationInterface $registration,
        string $deepLinkingReturnUrl,
        string $deploymentId = null,
        string $deppLinkingData = null,
        string $deppLinkingErrorMessage = null,
        string $deppLinkingErrorLog = null,
        array $optionalClaims = []
    ): LtiMessageInterface {
        try {
            $deppLinkingErrorMessage = $deppLinkingErrorMessage ?? 'An error occurred';
            $deppLinkingErrorLog = $deppLinkingErrorLog ?? $deppLinkingErrorMessage;

            $this->builder
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA, $deppLinkingData)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_ERROR_MESSAGE, $deppLinkingErrorMessage)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_ERROR_LOG, $deppLinkingErrorLog)
                ->withClaim(new DeepLinkingContentItemsClaim());

            return $this->buildToolLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                $deepLinkingReturnUrl,
                $deploymentId,
                $optionalClaims
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create deep linking launch error response: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
