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
 * @see https://www.imsglobal.org/spec/lti-bo/v1p1/#integration-with-lti-1-3
 */
class BasicOutcomeClaim implements MessagePayloadClaimInterface
{
    /** @var string */
    private $lisResultSourcedId;

    /** @var string */
    private $lisOutcomeServiceUrl;

    public function __construct(string $lisResultSourcedId, string $lisOutcomeServiceUrl)
    {
        $this->lisResultSourcedId = $lisResultSourcedId;
        $this->lisOutcomeServiceUrl = $lisOutcomeServiceUrl;
    }

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_BASIC_OUTCOME;
    }

    public function getLisResultSourcedId(): string
    {
        return $this->lisResultSourcedId;
    }

    public function getLisOutcomeServiceUrl(): string
    {
        return $this->lisOutcomeServiceUrl;
    }

    public function normalize(): array
    {
        return array_filter([
            'lis_result_sourcedid' => $this->lisResultSourcedId,
            'lis_outcome_service_url' => $this->lisOutcomeServiceUrl,
        ]);
    }

    public static function denormalize(array $claimData): BasicOutcomeClaim
    {
        return new self(
            $claimData['lis_result_sourcedid'],
            $claimData['lis_outcome_service_url']
        );
    }
}
