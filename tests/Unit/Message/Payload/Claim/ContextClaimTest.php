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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class ContextClaimTest extends TestCase
{
    /** @var ContextClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new ContextClaim('id', ['type'], 'label', 'title');
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('id', $this->subject->getIdentifier());
        $this->assertEquals(['type'], $this->subject->getTypes());
        $this->assertEquals('label', $this->subject->getLabel());
        $this->assertEquals('title', $this->subject->getTitle());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'id' => 'id',
                'type' => ['type'],
                'label' => 'label',
                'title' => 'title'
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = ContextClaim::denormalize([
            'id' => 'id',
            'type' => ['type'],
            'label' => 'label',
            'title' => 'title'
        ]);

        $this->assertInstanceOf(ContextClaim::class, $denormalisation);
        $this->assertEquals('id', $denormalisation->getIdentifier());
        $this->assertEquals(['type'], $denormalisation->getTypes());
        $this->assertEquals('label', $denormalisation->getLabel());
        $this->assertEquals('title', $denormalisation->getTitle());
    }
}
