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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\User\Result;

use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class UserAuthenticationResultTest extends TestCase
{
    use DomainTestingTrait;

    public function testAuthenticationSuccess(): void
    {
        $subject = new UserAuthenticationResult(true);

        $this->assertTrue($subject->isSuccess());
    }

    public function testAuthenticationFailure(): void
    {
        $subject = new UserAuthenticationResult(false);

        $this->assertFalse($subject->isSuccess());
    }

    public function testAnonymousAuthenticationSuccess(): void
    {
        $subject = new UserAuthenticationResult(true);

        $this->assertTrue($subject->isSuccess());
        $this->assertTrue($subject->isAnonymous());
    }

    public function testUserAuthenticationSuccess(): void
    {
        $userIdentity = $this->createTestUserIdentity();

        $subject = new UserAuthenticationResult(true, $userIdentity);

        $this->assertTrue($subject->isSuccess());
        $this->assertFalse($subject->isAnonymous());
        $this->assertEquals($userIdentity, $subject->getUserIdentity());
    }
}
