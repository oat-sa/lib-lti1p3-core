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
 * @see http://www.imsglobal.org/spec/lti/v1p3/#resource-link-claim-0
 */
class ResourceLinkClaim implements MessagePayloadClaimInterface
{
    /** @var string */
    private $id;

    /** @var string|null */
    private $title;

    /** @var string|null */
    private $description;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK;
    }

    public function __construct(string $id, ?string $title = null, ?string $description = null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function normalize(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description
        ]);
    }

    public static function denormalize(array $claimData): ResourceLinkClaim
    {
        return new self(
            $claimData['id'],
            $claimData['title'] ?? null,
            $claimData['description'] ?? null
        );
    }
}
