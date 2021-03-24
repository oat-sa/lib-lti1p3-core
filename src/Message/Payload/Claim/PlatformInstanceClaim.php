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
 * @see http://www.imsglobal.org/spec/lti/v1p3/#platform-instance-claim-0
 */
class PlatformInstanceClaim implements MessagePayloadClaimInterface
{
    /** @var string */
    private $guid;

    /** @var string|null */
    private $contactEmail;

    /** @var string|null */
    private $description;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $url;

    /** @var string|null */
    private $productFamilyCode;

    /** @var string|null */
    private $version;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_TOOL_PLATFORM;
    }

    public function __construct(
        string $guid,
        ?string $contactEmail = null,
        ?string $description = null,
        ?string $name = null,
        ?string $url = null,
        ?string $productFamilyCode = null,
        ?string $version = null
    ) {
        $this->guid = $guid;
        $this->contactEmail = $contactEmail;
        $this->description = $description;
        $this->name = $name;
        $this->url = $url;
        $this->productFamilyCode = $productFamilyCode;
        $this->version = $version;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getProductFamilyCode(): ?string
    {
        return $this->productFamilyCode;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function normalize(): array
    {
        return array_filter([
            'guid' => $this->guid,
            'contact_email' => $this->contactEmail,
            'description' => $this->description,
            'name' => $this->name,
            'url' => $this->url,
            'product_family_code' => $this->productFamilyCode,
            'version' => $this->version,
        ]);
    }

    public static function denormalize(array $claimData): PlatformInstanceClaim
    {
        return new self(
            (string)$claimData['guid'],
            $claimData['contact_email'] ?? null,
            $claimData['description'] ?? null,
            $claimData['name'] ?? null,
            $claimData['url'] ?? null,
            $claimData['product_family_code'] ?? null,
            $claimData['version'] ?? null
        );
    }
}
