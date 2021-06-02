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
 * @see https://www.imsglobal.org/spec/lti-ags/v2p0#capabilities-in-jwt-messages
 */
class AgsClaim implements MessagePayloadClaimInterface
{
    /** @var array */
    private $scopes;

    /** @var string|null */
    private $lineItemsContainerUrl;

    /** @var string|null */
    private $lineItemUrl;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_AGS;
    }

    public function __construct(array $scopes, ?string $lineItemsContainerUrl = null, ?string $lineItemUrl = null)
    {
        $this->scopes = $scopes;
        $this->lineItemsContainerUrl = $lineItemsContainerUrl;
        $this->lineItemUrl = $lineItemUrl;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getLineItemsContainerUrl(): ?string
    {
        return $this->lineItemsContainerUrl;
    }

    public function getLineItemUrl(): ?string
    {
        return $this->lineItemUrl;
    }

    public function normalize(): array
    {
        return array_filter([
            'scope' => $this->scopes,
            'lineitems' => $this->lineItemsContainerUrl,
            'lineitem' => $this->lineItemUrl
        ]);
    }

    public static function denormalize(array $claimData): AgsClaim
    {
        return new self(
            $claimData['scope'],
            $claimData['lineitems'] ?? null,
            $claimData['lineitem'] ?? null
        );
    }
}
