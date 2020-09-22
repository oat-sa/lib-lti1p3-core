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

use OAT\Library\Lti1p3Core\DeepLink\Settings\DeepLinkingSettingsInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\AbstractLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-request-message
 */
class DeepLinkingRequestBuilder extends AbstractLaunchRequestBuilder
{
    /**
     * @throws LtiExceptionInterface
     */
    public function build(
        DeepLinkingSettingsInterface $settings,
        RegistrationInterface $registration,
        string $loginHint,
        string $deploymentId = null,
        array $roles = [],
        array $optionalClaims = []
    ): LtiMessageInterface {
        try {
            $this->builder->withClaim(
                DeepLinkingSettingsClaim::denormalize([
                    'deep_link_return_url' => $settings->getDeepLinkingReturnUrl(),
                    'accept_types' => $settings->getAcceptedTypes(),
                    'accept_presentation_document_targets' => $settings->getAcceptedPresentationDocumentTargets(),
                    'accept_media_types' => $settings->getAcceptedMediaTypes(),
                    'accept_multiple' => $settings->shouldAcceptMultiple(),
                    'auto_create' => $settings->shouldAutoCreate(),
                    'title' => $settings->getTitle(),
                    'text' => $settings->getText(),
                    'data' => 'some data'
                ])
            );

            return $this->buildLaunchRequest(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
                $registration->getTool()->getDeepLinkingUrl(),
                $loginHint,
                $deploymentId,
                $roles,
                $optionalClaims
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create deep linking request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
