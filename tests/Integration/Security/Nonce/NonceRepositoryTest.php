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

        $this->cache->set('lti1p3-nonce-nonce', 'nonce');

        $nonce = $this->subject->find('nonce');

        $this->assertInstanceOf(NonceInterface::class, $nonce);
        $this->assertEquals('nonce', $nonce->getValue());
    }

    public function testSave(): void
    {
        $this->assertFalse($this->cache->has('lti1p3-nonce-nonce'));

        $nonce = new Nonce('nonce');

        $this->subject->save($nonce);

        $this->assertTrue($this->cache->has('lti1p3-nonce-nonce'));
        $this->assertEquals('nonce', $this->cache->get('lti1p3-nonce-nonce'));
    }
}
