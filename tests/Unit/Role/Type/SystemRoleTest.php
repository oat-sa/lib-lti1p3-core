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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Role\Type;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Role\RoleInterface;
use OAT\Library\Lti1p3Core\Role\Type\SystemRole;
use PHPUnit\Framework\TestCase;

class SystemRoleTest extends TestCase
{
    /**
     * @dataProvider provideValidRolesMap
     */
    public function testValidRoles(string $roleName, bool $isCore): void
    {
        $subject = new SystemRole($roleName);

        $this->assertEquals(RoleInterface::TYPE_SYSTEM, $subject::getType());
        $this->assertEquals(RoleInterface::NAMESPACE_SYSTEM, $subject::getNameSpace());
        $this->assertEquals($roleName, $subject->getName());
        $this->assertNull($subject->getSubName());
        $this->assertEquals($isCore, $subject->isCore());
    }

    /**
     * @dataProvider provideInvalidRoles
     */
    public function testInvalidRole(string $roleName): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('Role %s is invalid for type system', $roleName));

        new SystemRole($roleName);
    }

    public function provideValidRolesMap(): array
    {
        return [
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#None',
                'isCore' => true
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport',
                'isCore' => false
            ],
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#User',
                'isCore' => false
            ],
        ];
    }

    public function provideInvalidRoles(): array
    {
        return [
            [
                'roleName' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Invalid',
            ],
            [
                'roleName' => 'Invalid',
            ],
        ];
    }
}
