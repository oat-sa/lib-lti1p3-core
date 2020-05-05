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
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use PHPUnit\Framework\TestCase;

class NonceGeneratorTest extends TestCase
{
    public function testItGenerateUniqueValues(): void
    {
        $subject = new NonceGenerator();

        $nonce1 = $subject->generate();
        $nonce2 = $subject->generate();
        $nonce3 = $subject->generate();

        $this->assertNotEquals($nonce1->getValue(), $nonce2->getValue());
        $this->assertNotEquals($nonce1->getValue(), $nonce3->getValue());
        $this->assertNotEquals($nonce2->getValue(), $nonce3->getValue());
    }

    public function testItGeneratesWithDefaultTtl(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now);

        $subject = new NonceGenerator();

        $nonce = $subject->generate();

        $this->assertEquals($now->addSeconds(NonceGeneratorInterface::TTL), $nonce->getExpiredAt());
    }

    public function testItGeneratesWithGivenTtl(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now);

        $subject = new NonceGenerator();

        $nonce = $subject->generate(123);

        $this->assertEquals($now->addSeconds(123), $nonce->getExpiredAt());
    }
}
