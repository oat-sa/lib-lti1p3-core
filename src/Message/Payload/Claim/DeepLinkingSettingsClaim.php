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

namespace OAT\Library\Lti1p3Core\Message\Payload\Claim;

use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-settings
 */
class DeepLinkingSettingsClaim implements MessagePayloadClaimInterface
{
    /** @var string */
    private $deepLinkReturnUrl;

    /** @var array */
    private $acceptedTypes;

    /** @var array */
    private $acceptedPresentationDocumentTargets;

    /** @var string|null */
    private $acceptedMediaTypes;

    /** @var bool */
    private $acceptMultiple;

    /** @var bool */
    private $autoCreate;

    /** @var string|null */
    private $title;

    /** @var string|null */
    private $text;

    /** @var string|null */
    private $data;

    public function __construct(
        string $deepLinkReturnUrl,
        array $acceptedTypes,
        array $acceptedPresentationDocumentTargets,
        string $acceptedMediaTypes = null,
        bool $acceptMultiple = true,
        bool $autoCreate = false,
        string $title = null,
        string $text= null,
        string $data = null
    ) {
        $this->deepLinkReturnUrl = $deepLinkReturnUrl;
        $this->acceptedTypes = $acceptedTypes;
        $this->acceptedPresentationDocumentTargets = $acceptedPresentationDocumentTargets;
        $this->acceptedMediaTypes = $acceptedMediaTypes;
        $this->acceptMultiple = $acceptMultiple;
        $this->autoCreate = $autoCreate;
        $this->title = $title;
        $this->text = $text;
        $this->data = $data;
    }

    public function getDeepLinkReturnUrl(): string
    {
        return $this->deepLinkReturnUrl;
    }

    public function getAcceptedTypes(): array
    {
        return $this->acceptedTypes;
    }

    public function getAcceptedPresentationDocumentTargets(): array
    {
        return $this->acceptedPresentationDocumentTargets;
    }

    public function getAcceptedMediaTypes(): ?string
    {
        return $this->acceptedMediaTypes;
    }

    public function shouldAcceptMultiple(): bool
    {
        return $this->acceptMultiple;
    }

    public function shouldAutoCreate(): bool
    {
        return $this->autoCreate;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_SETTINGS;
    }

    public function normalize(): array
    {
        return array_filter([
            'deep_link_return_url' => $this->deepLinkReturnUrl,
            'accept_types' => $this->acceptedTypes,
            'accept_presentation_document_targets' => $this->acceptedPresentationDocumentTargets,
            'accept_media_types' => $this->acceptedMediaTypes,
            'accept_multiple' => $this->acceptMultiple,
            'auto_create' => $this->autoCreate,
            'title' => $this->title,
            'text' => $this->text,
            'data' => $this->data,
        ]);
    }

    public static function denormalize(array $claimData): DeepLinkingSettingsClaim
    {
        return new self(
            $claimData['deep_link_return_url'],
            $claimData['accept_types'],
            $claimData['accept_presentation_document_targets'],
            $claimData['accept_media_types'] ?? null,
            $claimData['accept_multiple'] ?? true,
            $claimData['auto_create'] ?? false,
            $claimData['title'] ?? null,
            $claimData['text'] ?? null,
            $claimData['data'] ?? null
        );
    }
}
