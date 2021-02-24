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
use OAT\Library\Lti1p3Core\Resource\ResourceCollection;
use OAT\Library\Lti1p3Core\Resource\ResourceCollectionInterface;
use OAT\Library\Lti1p3Core\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

class ResourceCollectionTest extends TestCase
{
    /** @var ResourceCollectionInterface */
    private $subject;

    protected function setUp(): void
    {
        $resource1 = new Resource('resource1', 'type1', ['title' => 'title1', 'text' => 'text1']);
        $resource2 = new Resource('resource2', 'type2', ['title' => 'title2', 'text' => 'text2']);

        $this->subject = new ResourceCollection([$resource1, $resource2]);
    }

    public function testItImplementsResourceCollectionInterface(): void
    {
        $this->assertInstanceOf(ResourceCollectionInterface::class, $this->subject);
    }

    public function testIterator(): void
    {
        foreach ($this->subject as $resource) {
            $this->assertInstanceOf(ResourceInterface::class, $resource);
        }
    }

    public function testCount(): void
    {
        $this->assertEquals(2, $this->subject->count());
    }

    public function testAdd(): void
    {
        $this->subject->add(
            new Resource('resource3', 'type3', ['title' => 'title3', 'text' => 'text1'])
        );

        $this->assertEquals(3, $this->subject->count());
    }

    public function testGetByType(): void
    {
        $this->assertEquals('resource1', current($this->subject->getByType('type1'))->getIdentifier());
        $this->assertEquals('resource2', current($this->subject->getByType('type2'))->getIdentifier());

        $this->assertEmpty($this->subject->getByType('invalid'));
    }

    public function testNormalize(): void
    {
        $this->assertEquals(
            [
                [
                    'type' => 'type1',
                    'title' => 'title1',
                    'text' => 'text1'
                ],
                [
                    'type' => 'type2',
                    'title' => 'title2',
                    'text' => 'text2'
                ]
            ],
            $this->subject->normalize()
        );
    }
}
