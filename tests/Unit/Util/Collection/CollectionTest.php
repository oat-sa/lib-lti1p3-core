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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Util\Collection;

use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testInterface(): void
    {
        $this->assertInstanceOf(CollectionInterface::class, new Collection());
    }

    public function testLifeCycle(): void
    {
        $subject = new Collection();

        $this->assertEmpty($subject->all());
        $this->assertEmpty($subject->keys());
        $this->assertEmpty($subject->getIterator());
        $this->assertEquals(0, $subject->count());

        $subject->add(
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ]
        );

        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            $subject->all()
        );
        $this->assertEquals(['key1', 'key2'], $subject->keys());
        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            $subject->getIterator()->getArrayCopy()
        );
        $this->assertEquals(2, $subject->count());
        $this->assertEquals('value1', $subject->get('key1'));
        $this->assertEquals('value2', $subject->get('key2'));
        $this->assertEquals('value1', $subject->getMandatory('key1'));
        $this->assertEquals('value2', $subject->getMandatory('key2'));

        $subject->set('key3', 'value3');

        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            $subject->all()
        );
        $this->assertEquals(['key1', 'key2', 'key3'], $subject->keys());
        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            $subject->getIterator()->getArrayCopy()
        );
        $this->assertEquals(3, $subject->count());
        $this->assertEquals('value1', $subject->get('key1'));
        $this->assertEquals('value2', $subject->get('key2'));
        $this->assertEquals('value3', $subject->get('key3'));
        $this->assertEquals('value1', $subject->getMandatory('key1'));
        $this->assertEquals('value2', $subject->getMandatory('key2'));
        $this->assertEquals('value3', $subject->getMandatory('key3'));

        $subject->remove('key1');

        $this->assertEquals(
            [
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            $subject->all()
        );
        $this->assertEquals(['key2', 'key3'], $subject->keys());
        $this->assertEquals(
            [
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            $subject->getIterator()->getArrayCopy()
        );
        $this->assertEquals(2, $subject->count());
        $this->assertNull($subject->get('key1'));
        $this->assertEquals('value2', $subject->get('key2'));
        $this->assertEquals('value3', $subject->get('key3'));
        $this->assertEquals('value2', $subject->getMandatory('key2'));
        $this->assertEquals('value3', $subject->getMandatory('key3'));

        $subject->replace(['key4' => 'value4']);

        $this->assertEquals(
            [
                'key4' => 'value4',
            ],
            $subject->all()
        );
        $this->assertEquals(['key4'], $subject->keys());
        $this->assertEquals(
            [
                'key4' => 'value4',
            ],
            $subject->getIterator()->getArrayCopy()
        );
        $this->assertEquals(1, $subject->count());
        $this->assertNull($subject->get('key1'));
        $this->assertNull($subject->get('key2'));
        $this->assertNull($subject->get('key3'));
        $this->assertEquals('value4', $subject->get('key4'));
        $this->assertEquals('value4', $subject->getMandatory('key4'));
    }

    public function testGetMissingItemWithDefaultValue(): void
    {
        $this->assertEquals('default', (new Collection())->get('missing', 'default'));
    }

    public function testItThrowsAnInvalidArgumentExceptionOnGetMissingMandatoryItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing mandatory invalid');

        (new Collection())->getMandatory('invalid');
    }
}
