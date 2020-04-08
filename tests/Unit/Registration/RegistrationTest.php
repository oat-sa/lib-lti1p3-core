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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Registration;

use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    use DomainTestingTrait;

    public function testGetters(): void
    {
        $subject = $this->createTestRegistration();

        $this->assertEquals('registrationIdentifier', $subject->getIdentifier());
        $this->assertEquals('registrationClientId', $subject->getClientId());
        $this->assertEquals($this->createTestPlatform(), $subject->getPlatform());
        $this->assertEquals($this->createTestTool(), $subject->getTool());
        $this->assertEquals(['deploymentIdentifier'], $subject->getDeploymentIds());
        $this->assertEquals('deploymentIdentifier', $subject->getDefaultDeploymentId());
        $this->assertTrue($subject->hasDeploymentId('deploymentIdentifier'));
        $this->assertFalse($subject->hasDeploymentId('invalid'));
        $this->assertEquals($this->createTestKeyChain('platformKeyChain'), $subject->getPlatformKeyChain());
        $this->assertEquals($this->createTestKeyChain('toolKeyChain'), $subject->getToolKeyChain());
        $this->assertNull($subject->getPlatformJwksUrl());
        $this->assertNull($subject->getToolJwksUrl());
    }

    public function testWithoutDeploymentIds(): void
    {
        $subject = new Registration(
            'registrationIdentifier',
            'registrationClientId',
            $this->createTestPlatform(),
            $this->createTestTool(),
            []
        );

        $this->assertEmpty($subject->getDeploymentIds());
        $this->assertNull($subject->getDefaultDeploymentId());
    }
}
