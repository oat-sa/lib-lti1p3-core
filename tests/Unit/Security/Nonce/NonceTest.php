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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Nonce;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use PHPUnit\Framework\TestCase;

class NonceTest extends TestCase
{
    public function testGetValue(): void
    {
        $subject = new Nonce('value');

        $this->assertEquals('value', $subject->getValue());
    }

    public function testGetExpiredAt(): void
    {
        $now = Carbon::now();

        $subject = new Nonce('value', $now);

        $this->assertEquals($now, $subject->getExpiredAt());
    }

    public function testExpiryWhenConstructedWithoutExpiredAt(): void
    {
        $subject = new Nonce('value');

        Carbon::setTestNow(Carbon::now()->subSecond());

        $this->assertFalse($subject->isExpired());

        Carbon::setTestNow(Carbon::now()->addSecond());

        $this->assertFalse($subject->isExpired());
    }

    public function testExpiryWhenConstructedWithExpiredAt(): void
    {
        Carbon::setTestNow();

        $subject = new Nonce('value', Carbon::now());

        Carbon::setTestNow(Carbon::now()->subSecond());

        $this->assertFalse($subject->isExpired());

        Carbon::setTestNow(Carbon::now()->addSecond());

        $this->assertTrue($subject->isExpired());
    }
}
