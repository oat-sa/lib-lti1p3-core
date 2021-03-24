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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\OAuth2\Validator\Result;

use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResult;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class RequestAccessTokenValidationResultTest extends TestCase
{
    use DomainTestingTrait;

    public function testGetRegistration(): void
    {
        $registration = $this->createTestRegistration();

        $subject = new RequestAccessTokenValidationResult($registration);

        $this->assertEquals($registration, $subject->getRegistration());
    }

    public function testGetToken(): void
    {
        $token = $this->buildJwt([], [], $this->createTestRegistration()->getPlatformKeyChain()->getPrivateKey());

        $subject = new RequestAccessTokenValidationResult(null, $token);

        $this->assertEquals($token, $subject->getToken());
    }

    public function testGetScopes(): void
    {
        $token = $this->buildJwt(
            [],
            [
                'scopes' => ['scope1', 'scope2']
            ],
            $this->createTestRegistration()->getPlatformKeyChain()->getPrivateKey()
        );

        $subject = new RequestAccessTokenValidationResult(null, $token);

        $this->assertEquals(['scope1', 'scope2'], $subject->getScopes());
    }

    public function testBehavior(): void
    {
        $subject = new RequestAccessTokenValidationResult();

        $this->assertFalse($subject->hasError());

        $subject->addSuccess('success');

        $this->assertFalse($subject->hasError());

        $subject->setError('error');

        $this->assertTrue($subject->hasError());

        $this->assertEquals(['success'], $subject->getSuccesses());
        $this->assertEquals('error', $subject->getError());
    }
}
