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

use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class KeyChainFactoryTest extends TestCase
{
    use SecurityTestingTrait;

    public function testCreate(): void
    {
        $subject = new KeyChainFactory();

        $keyChain = $this->createTestKeyChain();

        $result = $subject->create(
            $keyChain->getIdentifier(),
            $keyChain->getKeySetName(),
            $keyChain->getPublicKey()->getContent(),
            $keyChain->getPrivateKey()->getContent(),
            $keyChain->getPrivateKey()->getPassphrase()
        );

        $this->assertInstanceOf(KeyChainInterface::class, $result);
        $this->assertEquals($keyChain->getIdentifier(), $result->getIdentifier());
        $this->assertEquals($keyChain->getKeySetName(), $result->getKeySetName());
    }
}
