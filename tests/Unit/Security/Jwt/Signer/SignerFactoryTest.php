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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwt\Signer;

use Lcobucci\JWT\Signer\Ecdsa;
use Lcobucci\JWT\Signer\Hmac;
use Lcobucci\JWT\Signer\Rsa;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Jwt\Signer\SignerFactory;
use PHPUnit\Framework\TestCase;

class SignerFactoryTest extends TestCase
{
    /** @var SignerFactory */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new SignerFactory();
    }

    public function testCreateForES256(): void
    {
        $this->assertInstanceOf(Ecdsa\Sha256::class, $this->subject->create('ES256'));
    }

    public function testCreateForES384(): void
    {
        $this->assertInstanceOf(Ecdsa\Sha384::class, $this->subject->create('ES384'));
    }

    public function testCreateForES521(): void
    {
        $this->assertInstanceOf(Ecdsa\Sha512::class, $this->subject->create('ES512'));
    }

    public function testCreateForHS256(): void
    {
        $this->assertInstanceOf(Hmac\Sha256::class, $this->subject->create('HS256'));
    }

    public function testCreateForHS384(): void
    {
        $this->assertInstanceOf(Hmac\Sha384::class, $this->subject->create('HS384'));
    }

    public function testCreateForHS521(): void
    {
        $this->assertInstanceOf(Hmac\Sha512::class, $this->subject->create('HS512'));
    }

    public function testCreateForRS256(): void
    {
        $this->assertInstanceOf(Rsa\Sha256::class, $this->subject->create('RS256'));
    }

    public function testCreateForRS384(): void
    {
        $this->assertInstanceOf(Rsa\Sha384::class, $this->subject->create('RS384'));
    }

    public function testCreateForRS521(): void
    {
        $this->assertInstanceOf(Rsa\Sha512::class, $this->subject->create('RS512'));
    }

    public function testCreateFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Unhandled algorithm invalid');

        $this->subject->create('invalid');
    }
}
