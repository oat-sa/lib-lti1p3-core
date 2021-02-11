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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Key;

use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class KeyChainTest extends TestCase
{
    use SecurityTestingTrait;

    /** @var KeyChain */
    private $subject;

    public function setUp(): void
    {
        $this->subject = $this->createTestKeyChain();
    }

    public function testGetIdentifier(): void
    {
        $this->assertEquals('keyChainIdentifier', $this->subject->getIdentifier());
    }

    public function testKeySetName(): void
    {
        $this->assertEquals('keySetName', $this->subject->getKeySetName());
    }

    public function testGetPublicKey(): void
    {
        $this->assertInstanceOf(KeyInterface::class, $this->subject->getPublicKey());
    }

    public function testGetPrivateKey(): void
    {
        $this->assertInstanceOf(KeyInterface::class, $this->subject->getPrivateKey());
    }

    public function testWithoutPrivateKey(): void
    {
        $subject = new KeyChain(
            'identifier',
            'keySetName',
            new Key('test')
        );

        $this->assertNull($subject->getPrivateKey());
    }
}
