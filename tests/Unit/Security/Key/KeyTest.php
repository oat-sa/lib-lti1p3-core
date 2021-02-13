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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Key;

use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use PHPUnit\Framework\TestCase;

class KeyTest extends TestCase
{
    public function testStreamKey(): void
    {
        $subject = new Key('key', 'secret');

        $this->assertInstanceOf(KeyInterface::class, $subject);
        $this->assertEquals('key', $subject->getContent());
        $this->assertEquals('secret', $subject->getPassPhrase());
        $this->assertEquals(KeyInterface::ALG_RS256, $subject->getAlgorithm());
        $this->assertTrue($subject->isFromString());
        $this->assertFalse($subject->isFromArray());
        $this->assertFalse($subject->isFromFile());
    }

    public function testStreamKeyWithNonDefaultAlgorithm(): void
    {
        $subject = new Key('key', 'secret', 'HS256');

        $this->assertInstanceOf(KeyInterface::class, $subject);
        $this->assertEquals('key', $subject->getContent());
        $this->assertEquals('secret', $subject->getPassPhrase());
        $this->assertEquals('HS256', $subject->getAlgorithm());
        $this->assertTrue($subject->isFromString());
        $this->assertFalse($subject->isFromArray());
        $this->assertFalse($subject->isFromFile());
    }

    public function testFileKey(): void
    {
        $subject = new Key('file://path/to/key', 'secret');

        $this->assertInstanceOf(KeyInterface::class, $subject);
        $this->assertEquals('file://path/to/key', $subject->getContent());
        $this->assertEquals('secret', $subject->getPassPhrase());
        $this->assertEquals(KeyInterface::ALG_RS256, $subject->getAlgorithm());
        $this->assertFalse($subject->isFromString());
        $this->assertFalse($subject->isFromArray());
        $this->assertTrue($subject->isFromFile());
    }

    public function testArrayKey(): void
    {
        $subject = new Key(['use' => 'sig'], 'secret');

        $this->assertInstanceOf(KeyInterface::class, $subject);
        $this->assertEquals(['use' => 'sig'], $subject->getContent());
        $this->assertEquals('secret', $subject->getPassPhrase());
        $this->assertEquals(KeyInterface::ALG_RS256, $subject->getAlgorithm());
        $this->assertFalse($subject->isFromString());
        $this->assertTrue($subject->isFromArray());
        $this->assertFalse($subject->isFromFile());
    }
}
