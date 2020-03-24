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

use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class KeyChainRepositoryTest extends TestCase
{
    use SecurityTestingTrait;

    /** @var KeyChainRepository */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new KeyChainRepository([
            $this->createTestKeyChain('keyIdentifier1', 'keySetName1'),
            $this->createTestKeyChain('keyIdentifier2', 'keySetName2'),
            $this->createTestKeyChain('keyIdentifier3', 'keySetName1')
        ]);
    }

    public function testItCanFindAKeyChainByIdentifier(): void
    {
        $keyChain = $this->subject->find('keyIdentifier1');

        $this->assertInstanceOf(KeyChainInterface::class, $keyChain);
        $this->assertEquals('keyIdentifier1', $keyChain->getIdentifier());
    }

    public function testItReturnNullWhenFindingWithInvalidIdentifier(): void
    {
        $this->assertNull($this->subject->find('invalid'));
    }

    public function testItCanFindKeyChainsByKeySetName(): void
    {
        $keyChains = $this->subject->findByKeySetName('keySetName1');

        $this->assertCount(2, $keyChains);

        $keyChain = current($keyChains);
        $this->assertEquals('keyIdentifier1', $keyChain->getIdentifier());

        $keyChain = next($keyChains);
        $this->assertEquals('keyIdentifier3', $keyChain->getIdentifier());
    }
}
