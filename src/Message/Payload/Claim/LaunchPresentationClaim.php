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
 * @see http://www.imsglobal.org/spec/lti/v1p3/#launch-presentation-claim-0
 */
class LaunchPresentationClaim implements MessagePayloadClaimInterface
{
    /** @var string|null */
    private $documentTarget;

    /** @var string|null */
    private $height;

    /** @var string|null */
    private $width;

    /** @var string|null */
    private $returnUrl;

    /** @var string|null */
    private $locale;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_LAUNCH_PRESENTATION;
    }

    public function __construct(
        ?string $documentTarget = null,
        ?string $height = null,
        ?string $width = null,
        ?string $returnUrl = null,
        ?string $locale = null
    ) {
        $this->documentTarget = $documentTarget;
        $this->height = $height;
        $this->width = $width;
        $this->returnUrl = $returnUrl;
        $this->locale = $locale;
    }

    public function getDocumentTarget(): ?string
    {
        return $this->documentTarget;
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function normalize(): array
    {
       return array_filter([
            'document_target' => $this->documentTarget,
            'height' => $this->height,
            'width' => $this->width,
            'return_url' => $this->returnUrl,
            'locale' => $this->locale,
        ]);
    }

    public static function denormalize(array $claimData): LaunchPresentationClaim
    {
        return new self(
            $claimData['document_target'] ?? null,
            isset($claimData['height']) ? (string)$claimData['height'] : null,
            isset($claimData['width']) ? (string)$claimData['width'] : null,
            $claimData['return_url'] ?? null,
            $claimData['locale'] ?? null
        );
    }
}
