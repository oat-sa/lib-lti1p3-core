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
 * @see https://www.imsglobal.org/spec/lti-nrps/v2p0#claim-for-inclusion-in-lti-messages
 */
class NrpsClaim implements MessagePayloadClaimInterface
{
    /** @var string */
    private $contextMembershipsUrl;

    /** @var string[] */
    private $serviceVersions;

    public function __construct(string $contextMembershipsUrl, array $serviceVersions = [])
    {
        $this->contextMembershipsUrl = $contextMembershipsUrl;
        $this->serviceVersions = $serviceVersions;
    }

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_NRPS;
    }

    public function getContextMembershipsUrl(): string
    {
        return $this->contextMembershipsUrl;
    }

    public function getServiceVersions(): array
    {
        return $this->serviceVersions;
    }

    public function normalize(): array
    {
        return array_filter([
            'context_memberships_url' => $this->contextMembershipsUrl,
            'service_versions' => $this->serviceVersions,
        ]);
    }

    public static function denormalize(array $claimData): NrpsClaim
    {
        return new self(
            $claimData['context_memberships_url'],
            $claimData['service_versions'] ?? []
        );
    }
}
