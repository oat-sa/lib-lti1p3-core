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
use OAT\Library\Lti1p3Core\Message\Launch\Builder\AbstractLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingContentItems;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Resource\ResourceCollectionInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-message
 */
class DeepLinkingResponseBuilder extends AbstractLaunchRequestBuilder
{
    /**
     * @throws LtiExceptionInterface
     */
    public function build(
        ResourceCollectionInterface $resourceCollection,
        RegistrationInterface $registration,
        string $deepLinkingReturnUrl,
        string $data = null,
        string $deploymentId = null
    ): LtiMessageInterface {
        try {
            $contentItemsClaim = new DeepLinkingContentItems($resourceCollection->jsonSerialize());

            $deploymentId = $this->resolveDeploymentId($registration, $deploymentId);

            $this->builder
                ->withClaim(MessagePayloadInterface::CLAIM_AUD, $registration->getPlatform()->getAudience())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_VERSION, LtiMessageInterface::LTI_VERSION)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE, LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID, $deploymentId)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_CONTENT_ITEMS, $contentItemsClaim)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA, $data)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_MESSAGE, sprintf('%s content items provided', $resourceCollection->count()));

            $responsePayload = $this->builder->buildMessagePayload($registration->getToolKeyChain());

            return new LtiMessage(
                $deepLinkingReturnUrl,
                [
                    'JWT' => $responsePayload->getToken()->__toString(),
                ]
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create deep linking response: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
