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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Role\Collection;

use OAT\Library\Lti1p3Core\Role\Collection\RoleCollection;
use OAT\Library\Lti1p3Core\Role\Factory\RoleFactory;
use OAT\Library\Lti1p3Core\Role\RoleInterface;
use PHPUnit\Framework\TestCase;

class RoleCollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $systemRole = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator');

        $subject = new RoleCollection([$systemRole]);

        $this->assertEquals(1, $subject->count());

        $this->assertTrue($subject->has($systemRole->getName()));
        $this->assertSame($systemRole, $subject->get($systemRole->getName()));
    }

    public function testLifecycle(): void
    {
        $subject = new RoleCollection();

        $this->assertEquals(0, $subject->count());

        $systemRole = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator');

        $subject->add($systemRole);

        $this->assertEquals(1, $subject->count());

        $this->assertTrue($subject->has($systemRole->getName()));
        $this->assertSame($systemRole, $subject->get($systemRole->getName()));

        $institutionRole = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator');

        $subject->add($institutionRole);

        $this->assertEquals(2, $subject->count());

        $this->assertTrue($subject->has($institutionRole->getName()));
        $this->assertSame($institutionRole, $subject->get($institutionRole->getName()));

        $contextRole = RoleFactory::create('Learner');

        $subject->add($contextRole);

        $this->assertEquals(3, $subject->count());

        $this->assertTrue($subject->has($contextRole->getName()));
        $this->assertSame($contextRole, $subject->get($contextRole->getName()));

        $this->assertEquals(
            [
                $systemRole->getName() => $systemRole,
                $institutionRole->getName() => $institutionRole,
                $contextRole->getName() => $contextRole
            ],
            $subject->all()
        );
    }

    public function testFind(): void
    {
        $systemRole = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator');
        $institutionRole = RoleFactory::create('http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator');
        $contextRole = RoleFactory::create('Learner');

        $subject = new RoleCollection([$systemRole, $institutionRole, $contextRole]);

        $this->assertTrue($subject->canFindBy(RoleInterface::TYPE_SYSTEM, true));
        $this->assertFalse($subject->canFindBy(RoleInterface::TYPE_SYSTEM, false));
        $this->assertEquals(
            [
                $systemRole->getName() => $systemRole
            ],
            $subject->findBy(RoleInterface::TYPE_SYSTEM, true)
        );

        $this->assertTrue($subject->canFindBy(RoleInterface::TYPE_INSTITUTION, true));
        $this->assertFalse($subject->canFindBy(RoleInterface::TYPE_INSTITUTION, false));
        $this->assertEquals(
            [
                $institutionRole->getName() => $institutionRole
            ],
            $subject->findBy(RoleInterface::TYPE_INSTITUTION, true)
        );

        $this->assertTrue($subject->canFindBy(RoleInterface::TYPE_CONTEXT, true));
        $this->assertFalse($subject->canFindBy(RoleInterface::TYPE_CONTEXT, false));
        $this->assertEquals(
            [
                $contextRole->getName() => $contextRole
            ],
            $subject->findBy(RoleInterface::TYPE_CONTEXT, true)
        );

        $this->assertTrue($subject->canFindBy(null, true));
        $this->assertFalse($subject->canFindBy(null, false));
        $this->assertEquals(
            [
                $systemRole->getName() => $systemRole,
                $institutionRole->getName() => $institutionRole,
                $contextRole->getName() => $contextRole
            ],
            $subject->findBy(null, true)
        );

        $this->assertEquals($subject->all(), $subject->findBy());
    }
}
