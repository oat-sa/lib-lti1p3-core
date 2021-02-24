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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Resource;

use OAT\Library\Lti1p3Core\Resource\Resource;
use OAT\Library\Lti1p3Core\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    /** @var ResourceInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new Resource(
            'identifier',
            'type',
            [
                'title' => 'title',
                'text' => 'text'
            ]
        );
    }

    public function testItImplementsResourceInterface(): void
    {
        $this->assertInstanceOf(ResourceInterface::class, $this->subject);
    }

    public function testGetIdentifier(): void
    {
        $this->assertEquals('identifier', $this->subject->getIdentifier());
    }

    public function testGetType(): void
    {
        $this->assertEquals('type', $this->subject->getType());
    }

    public function testGetTitle(): void
    {
        $this->assertEquals('title', $this->subject->getTitle());
    }

    public function testGetText(): void
    {
        $this->assertEquals('text', $this->subject->getText());
    }

    public function testProperties(): void
    {
        $this->assertTrue($this->subject->getProperties()->has('title'));
        $this->assertTrue($this->subject->getProperties()->has('text'));
        $this->assertEquals('title', $this->subject->getProperties()->getMandatory('title'));
        $this->assertEquals('text', $this->subject->getProperties()->getMandatory('text'));

        $this->assertFalse($this->subject->getProperties()->has('missing'));
        $this->assertEquals('default', $this->subject->getProperties()->get('missing', 'default'));

        $this->assertEquals(
            [
                'title' => 'title',
                'text' => 'text'
            ],
            $this->subject->getProperties()->all()
        );
    }

    public function testNormalize(): void
    {
        $this->assertEquals(
            [
                'type' => 'type',
                'title' => 'title',
                'text' => 'text'
            ],
            $this->subject->normalize()
        );
    }
}
