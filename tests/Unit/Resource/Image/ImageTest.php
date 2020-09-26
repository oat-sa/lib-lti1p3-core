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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Resource\Image;

use OAT\Library\Lti1p3Core\Resource\Image\Image;
use OAT\Library\Lti1p3Core\Resource\Image\ImageInterface;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /** @var ImageInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new Image(
            'identifier',
            'url',
            [
                'icon' => ['icon'],
                'thumbnail' => ['thumbnail'],
                'width' => 'width',
                'height' => 'height',
            ]
        );
    }

    public function testItImplementsImageInterface(): void
    {
        $this->assertInstanceOf(ImageInterface::class, $this->subject);
    }

    public function testPropertiesGetters(): void
    {
        $this->assertEquals('url', $this->subject->getUrl());
        $this->assertEquals(['icon'], $this->subject->getIcon());
        $this->assertEquals(['thumbnail'], $this->subject->getThumbnail());
        $this->assertEquals('width', $this->subject->getWidth());
        $this->assertEquals('height', $this->subject->getHeight());
    }
}
