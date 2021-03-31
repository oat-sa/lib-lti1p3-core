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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Util\Result;

use OAT\Library\Lti1p3Core\Util\Result\Result;
use OAT\Library\Lti1p3Core\Util\Result\ResultInterface;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testInterface(): void
    {
        $this->assertInstanceOf(ResultInterface::class, new Result());
    }

    public function testConstructor(): void
    {
        $subject = new Result(['success1', 'success2'], 'error');

        $this->assertEquals(['success1', 'success2'], $subject->getSuccesses());
        $this->assertTrue($subject->hasError());
        $this->assertEquals('error', $subject->getError());
    }

    public function testLifeCycle(): void
    {
        $subject = new Result();

        $this->assertEmpty($subject->getSuccesses());
        $this->assertFalse($subject->hasError());
        $this->assertNull($subject->getError());

        $subject->addSuccess('success1');

        $this->assertEquals(['success1'], $subject->getSuccesses());
        $this->assertFalse($subject->hasError());
        $this->assertNull($subject->getError());

        $subject->addSuccess('success2');

        $this->assertEquals(['success1', 'success2'], $subject->getSuccesses());
        $this->assertFalse($subject->hasError());
        $this->assertNull($subject->getError());

        $subject->setSuccesses(['success3', 'success4']);

        $this->assertEquals(['success3', 'success4'], $subject->getSuccesses());
        $this->assertFalse($subject->hasError());
        $this->assertNull($subject->getError());

        $subject->setError('error');

        $this->assertTrue($subject->hasError());
        $this->assertEquals('error', $subject->getError());
    }
}
