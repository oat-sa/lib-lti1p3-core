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
use DateTimeInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use PHPUnit\Framework\TestCase;

class NonceTest extends TestCase
{
    public function testNonceComponents(): void
    {
        $now = Carbon::now();
        $nonce = new Nonce('nonce_key', $now);

        $this->assertEquals('nonce_key', $nonce->getValue());
        $this->assertEquals($now, $nonce->getExpiredAt());
    }

    /**
     * @dataProvider expirationProvider
     */
    public function testNonceExpiration(bool $expected, DateTimeInterface $expiredAt): void
    {
        $nonce = new Nonce('nonce_key', $expiredAt);

        $this->assertEquals($expected, $nonce->isExpired());
    }

    public function expirationProvider(): array
    {
        return [
            'nonce is valid' => [false, Carbon::now()->addSeconds(10)],
            'nonce is expired' => [true, Carbon::now()->subSecond()]
        ];
    }
}
