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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwt\Validator;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    use DomainTestingTrait;

    /** @var ValidatorInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new Validator();
    }

    public function testValidationSuccess(): void
    {
        $registration = $this->createTestRegistration();

        $token = $this->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey());

        $this->assertTrue($this->subject->validate($token, $registration->getPlatformKeyChain()->getPublicKey()));
    }

    public function testValidationFailureOnInvalidTokenSignature(): void
    {
        $registration = $this->createTestRegistration();

        $token = $this->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey());

        $this->assertFalse($this->subject->validate($token, new Key(__DIR__ . '/../../../Resource/Key/DSA/public.key')));
    }

    public function testValidationFailureOnExpiredToken(): void
    {
        $registration = $this->createTestRegistration();

        Carbon::setTestNow(Carbon::now()->subSeconds(MessagePayloadInterface::TTL + 1));
        $token = $this->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey());
        Carbon::setTestNow();


        $this->assertFalse($this->subject->validate($token, $registration->getPlatformKeyChain()->getPublicKey()));
    }
}
