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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class ResourceLinkClaimTest extends TestCase
{
    /** @var ResourceLinkClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new ResourceLinkClaim('id', 'title', 'description');
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('id', $this->subject->getIdentifier());
        $this->assertEquals('title', $this->subject->getTitle());
        $this->assertEquals('description', $this->subject->getDescription());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'id' => 'id',
                'title' => 'title',
                'description' => 'description'
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = ResourceLinkClaim::denormalize([
            'id' => 'id',
            'title' => 'title',
            'description' => 'description'
        ]);

        $this->assertInstanceOf(ResourceLinkClaim::class, $denormalisation);
        $this->assertEquals('id', $denormalisation->getIdentifier());
        $this->assertEquals('title', $denormalisation->getTitle());
        $this->assertEquals('description', $denormalisation->getDescription());
    }
}
