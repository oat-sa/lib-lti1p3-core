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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class AgsClaimTest extends TestCase
{
    /** @var AgsClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new AgsClaim(['scope1, scope2'], 'lineItemsContainerUrl', 'lineItemUrl');
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_AGS, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals(['scope1, scope2'], $this->subject->getScopes());
        $this->assertEquals('lineItemsContainerUrl', $this->subject->getLineItemsContainerUrl());
        $this->assertEquals('lineItemUrl', $this->subject->getLineItemUrl());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'scope' => ['scope1, scope2'],
                'lineitems' => 'lineItemsContainerUrl',
                'lineitem' => 'lineItemUrl'
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = AgsClaim::denormalize([
            'scope' => ['scope1, scope2'],
            'lineitems' => 'lineItemsContainerUrl',
            'lineitem' => 'lineItemUrl'
        ]);

        $this->assertInstanceOf(AgsClaim::class, $denormalisation);
        $this->assertEquals(['scope1, scope2'], $denormalisation->getScopes());
        $this->assertEquals('lineItemsContainerUrl', $denormalisation->getLineItemsContainerUrl());
        $this->assertEquals('lineItemUrl', $denormalisation->getLineItemUrl());
    }
}
