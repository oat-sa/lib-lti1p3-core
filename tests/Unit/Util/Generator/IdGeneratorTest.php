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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Util\Generator;

use OAT\Library\Lti1p3Core\Util\Generator\IdGenerator;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

class IdGeneratorTest extends TestCase
{
    public function testInterface(): void
    {
        $this->assertInstanceOf(IdGeneratorInterface::class, new IdGenerator());
    }

    public function testUniqueGenerations(): void
    {
        $subject = new IdGenerator();

        $id1 = $subject->generate();
        $id2 = $subject->generate();
        $id3 = $subject->generate();

        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($id2, $id3);
        $this->assertNotEquals($id1, $id3);
    }
}
