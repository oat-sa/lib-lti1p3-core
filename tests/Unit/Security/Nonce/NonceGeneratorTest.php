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
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use PHPUnit\Framework\TestCase;

class NonceGeneratorTest extends TestCase
{
    /**
     * @dataProvider providerTestItGenerates
     */
    public function testItGeneratesFromDifferentTtl(Nonce $nonce, int $expectedExpirationTimeStamp): void
    {
        $this->assertEquals($nonce->getExpiredAt()->getTimestamp(), $expectedExpirationTimeStamp);
    }

    public function providerTestItGenerates(): array
    {
        $knownDate = Carbon::create(1988, 12, 22, 06);

        Carbon::setTestNow($knownDate);
        $nonceGivenTtl = (new NonceGenerator())->generate(6);
        $nonceConstructedTtl = (new NonceGenerator(60))->generate();
        $nonceDefaultTtl = (new NonceGenerator())->generate();
        $nonceGivenAndConstructedTtl = (new NonceGenerator(99))->generate(1);

        return [
            'It Generates From Given TTL' => [
                $nonceGivenTtl,
                (Carbon::create(1988, 12, 22, 06))->addSeconds(6)->getTimestamp()
            ],
            'It Generates From Constructed TTL' => [
                $nonceConstructedTtl,
                (Carbon::create(1988, 12, 22, 06))->addSeconds(60)->getTimestamp()
            ],
            'It Generates From Default TTL' => [
                $nonceDefaultTtl,
                (Carbon::create(1988, 12, 22, 06))->addSeconds(600)->getTimestamp()
            ],
            'It Generates From Given and Constructed TTL' => [
                $nonceGivenAndConstructedTtl,
                (Carbon::create(1988, 12, 22, 06))->addSecond()->getTimestamp()
            ],
        ];
    }

    public function testItGeneratesUniqueValues(): void
    {
        $values = [];

        for ($i = 0; $i < 100; $i++) {
            $values[] = (new NonceGenerator())->generate()->getValue();
        }

        $this->assertEquals(array_unique($values), $values);
    }
}
