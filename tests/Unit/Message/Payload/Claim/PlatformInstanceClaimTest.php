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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Claim;

use OAT\Library\Lti1p3Core\Token\Claim\PlatformInstanceTokenClaim;
use OAT\Library\Lti1p3Core\Token\LtiMessageTokenInterface;
use PHPUnit\Framework\TestCase;

class PlatformInstanceClaimTest extends TestCase
{
    /** @var PlatformInstanceTokenClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new PlatformInstanceTokenClaim(
            'guid',
            'contact_email',
            'description',
            'name',
            'url',
            'product_family_code',
            'version'
        );
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessageTokenInterface::CLAIM_LTI_TOOL_PLATFORM, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('guid', $this->subject->getGuid());
        $this->assertEquals('contact_email', $this->subject->getContactEmail());
        $this->assertEquals('description', $this->subject->getDescription());
        $this->assertEquals('name', $this->subject->getName());
        $this->assertEquals('url', $this->subject->getUrl());
        $this->assertEquals('product_family_code', $this->subject->getProductFamilyCode());
        $this->assertEquals('version', $this->subject->getVersion());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'guid' => 'guid',
                'contact_email' => 'contact_email',
                'description' => 'description',
                'name' => 'name',
                'url' => 'url',
                'product_family_code' => 'product_family_code',
                'version' => 'version'
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = PlatformInstanceTokenClaim::denormalize([
            'guid' => 'guid',
            'contact_email' => 'contact_email',
            'description' => 'description',
            'name' => 'name',
            'url' => 'url',
            'product_family_code' => 'product_family_code',
            'version' => 'version'
        ]);

        $this->assertInstanceOf(PlatformInstanceTokenClaim::class, $denormalisation);
        $this->assertEquals('guid', $denormalisation->getGuid());
        $this->assertEquals('contact_email', $denormalisation->getContactEmail());
        $this->assertEquals('description', $denormalisation->getDescription());
        $this->assertEquals('name', $denormalisation->getName());
        $this->assertEquals('url', $denormalisation->getUrl());
        $this->assertEquals('product_family_code', $denormalisation->getProductFamilyCode());
        $this->assertEquals('version', $denormalisation->getVersion());
    }
}
