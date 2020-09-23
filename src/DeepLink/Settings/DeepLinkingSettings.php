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

namespace OAT\Library\Lti1p3Core\DeepLink\Settings;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-settings
 */
class DeepLinkingSettings implements DeepLinkingSettingsInterface
{
    /** @var string */
    private $deepLinkingReturnUrl;

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

    public function __construct(
        string $deepLinkingReturnUrl,
        array $acceptedTypes,
        array $acceptedPresentationDocumentTargets,
        string $acceptedMediaTypes = null,
        bool $acceptMultiple = true,
        bool $autoCreate = false,
        string $title = null,
        string $text= null
    ) {
        $this->deepLinkingReturnUrl = $deepLinkingReturnUrl;
        $this->acceptedTypes = $acceptedTypes;
        $this->acceptedPresentationDocumentTargets = $acceptedPresentationDocumentTargets;
        $this->acceptedMediaTypes = $acceptedMediaTypes;
        $this->acceptMultiple = $acceptMultiple;
        $this->autoCreate = $autoCreate;
        $this->title = $title;
        $this->text = $text;
    }

    public function getDeepLinkingReturnUrl(): string
    {
        return $this->deepLinkingReturnUrl;
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
}
