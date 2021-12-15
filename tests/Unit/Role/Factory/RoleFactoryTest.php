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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Role\Factory;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Role\Factory\RoleFactory;
use OAT\Library\Lti1p3Core\Role\Type\ContextRole;
use OAT\Library\Lti1p3Core\Role\Type\InstitutionRole;
use OAT\Library\Lti1p3Core\Role\Type\LtiSystemRole;
use OAT\Library\Lti1p3Core\Role\Type\SystemRole;
use PHPUnit\Framework\TestCase;

class RoleFactoryTest extends TestCase
{
    public function testCreateSystemRole(): void
    {
        $result = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator');

        $this->assertInstanceOf(SystemRole::class, $result);
        $this->assertEquals('http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator', $result->getName());
        $this->assertNull($result->getSubName());
        $this->assertTrue($result->isCore());
    }

    public function testCreateInstitutionRole(): void
    {
        $result = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator');

        $this->assertInstanceOf(InstitutionRole::class, $result);
        $this->assertEquals('http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator', $result->getName());
        $this->assertNull($result->getSubName());
        $this->assertTrue($result->isCore());
    }

    public function testCreateContextRole(): void
    {
        $result = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator');

        $this->assertInstanceOf(ContextRole::class, $result);
        $this->assertEquals('http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator', $result->getName());
        $this->assertNull($result->getSubName());
        $this->assertTrue($result->isCore());
    }

    public function testCreateContextShortRole(): void
    {
        $result = RoleFactory::create('Administrator');

        $this->assertInstanceOf(ContextRole::class, $result);
        $this->assertEquals('Administrator', $result->getName());
        $this->assertNull($result->getSubName());
        $this->assertTrue($result->isCore());
    }

    public function testCreateContextSubRole(): void
    {
        $result = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator');

        $this->assertInstanceOf(ContextRole::class, $result);
        $this->assertEquals('http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator', $result->getName());
        $this->assertEquals('Administrator', $result->getSubName());
        $this->assertFalse($result->isCore());
    }

    public function testCreateLtiSystemRole(): void
    {
        $result = RoleFactory::create('http://purl.imsglobal.org/vocab/lti/system/person#TestUser');

        $this->assertInstanceOf(LtiSystemRole::class, $result);
        $this->assertEquals('http://purl.imsglobal.org/vocab/lti/system/person#TestUser', $result->getName());
        $this->assertNull($result->getSubName());
        $this->assertTrue($result->isCore());
    }

    public function testCreateFailure(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('Role Invalid is invalid');

        RoleFactory::create('Invalid');
    }
}
