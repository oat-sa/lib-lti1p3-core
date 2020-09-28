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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload\Claim;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\NrpsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class NrpsClaimTest extends TestCase
{
    /** @var NrpsClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new NrpsClaim('contextMembershipsUrl', ['1.0', '2.0']);
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_NRPS, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('contextMembershipsUrl', $this->subject->getContextMembershipsUrl());
        $this->assertEquals(['1.0', '2.0'], $this->subject->getServiceVersions());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'context_memberships_url' => 'contextMembershipsUrl',
                'service_versions' => ['1.0', '2.0']
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = NrpsClaim::denormalize([
            'context_memberships_url' => 'contextMembershipsUrl',
            'service_versions' => ['1.0', '2.0']
        ]);

        $this->assertInstanceOf(NrpsClaim::class, $denormalisation);
        $this->assertEquals('contextMembershipsUrl', $this->subject->getContextMembershipsUrl());
        $this->assertEquals(['1.0', '2.0'], $this->subject->getServiceVersions());
    }
}
