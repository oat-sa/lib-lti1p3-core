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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\Nonce;

use Cache\Adapter\PHPArray\ArrayCachePool;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepository;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NonceRepositoryTest extends TestCase
{
    /** @var ArrayCachePool */
    private $cache;

    /** @var NonceRepository */
    private $subject;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();

        $this->subject = new NonceRepository($this->cache);
    }

    public function testFind(): void
    {
        $this->assertNull($this->subject->find('nonce'));

        $this->cache->set('lti1p3-nonce-eDd7UldXtJRCf4kBT5fXmSjzk40U61HiD7XeyYNOswQ', 'nonce');

        $nonce = $this->subject->find('nonce');

        $this->assertInstanceOf(NonceInterface::class, $nonce);
        $this->assertEquals('nonce', $nonce->getValue());
    }

    public function testSave(): void
    {
        $this->assertFalse($this->cache->has('lti1p3-nonce-eDd7UldXtJRCf4kBT5fXmSjzk40U61HiD7XeyYNOswQ'));

        $nonce = new Nonce('nonce');

        $this->subject->save($nonce);

        $this->assertTrue($this->cache->has('lti1p3-nonce-eDd7UldXtJRCf4kBT5fXmSjzk40U61HiD7XeyYNOswQ'));
        $this->assertEquals('nonce', $this->cache->get('lti1p3-nonce-eDd7UldXtJRCf4kBT5fXmSjzk40U61HiD7XeyYNOswQ'));
    }

    public function testSaveAndFindWithReservedCharacters(): void
    {
        $nonce = new Nonce('nonce{}()/\@:value');

        $this->subject->save($nonce);

        $result = $this->subject->find('nonce{}()/\@:value');

        $this->assertInstanceOf(NonceInterface::class, $result);
        $this->assertEquals('nonce{}()/\@:value', $result->getValue());
    }

    public function testKeyStaysWithinTheLengthPsr6Guarantees(): void
    {
        $method = new ReflectionMethod(NonceRepository::class, 'getNonceCacheKey');
        $method->setAccessible(true);

        // PSR-6 guarantees support for 64 characters and no more, and the nonce arrives from the
        // platform, so its length is not ours to assume. The digest makes the key the same size
        // whatever turns up; an encoding proportional to the input fails this.
        $this->assertLessThanOrEqual(64, strlen($method->invoke($this->subject, 'nonce')));
        $this->assertLessThanOrEqual(64, strlen($method->invoke($this->subject, str_repeat('a', 4096))));
    }
}
