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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingContentItemsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Resource\Resource;
use OAT\Library\Lti1p3Core\Resource\ResourceCollection;
use PHPUnit\Framework\TestCase;

class DeepLinkingContentItemsClaimTest extends TestCase
{
    /** @var DeepLinkingContentItemsClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new DeepLinkingContentItemsClaim(['item']);
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_CONTENT_ITEMS, $this->subject::getClaimName());
    }

    public function testFromResourceCollection(): void
    {
        $collection = new ResourceCollection([
            new Resource('resource1', 'type1', ['property1' => 'value1']),
            new Resource('resource2', 'type2', ['property2' => 'value2']),
        ]);

        $subject = DeepLinkingContentItemsClaim::fromResourceCollection($collection);

        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'property1' => 'value1'
                ],
                [
                    'type' => 'type2',
                    'property2' => 'value2'
                ]
            ],
            $subject->getContentItems()
        );
    }

    public function testGetters(): void
    {
        $this->assertEquals(['item'], $this->subject->getContentItems());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(['item'], $this->subject->normalize());
    }

    public function testDenormalisation(): void
    {
        $denormalisation = DeepLinkingContentItemsClaim::denormalize(['item']);

        $this->assertInstanceOf(DeepLinkingContentItemsClaim::class, $denormalisation);
        $this->assertEquals(['item'], $denormalisation->getContentItems());
    }
}
