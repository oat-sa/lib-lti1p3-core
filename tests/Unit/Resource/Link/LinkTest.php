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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Resource\Link;

use OAT\Library\Lti1p3Core\Resource\Link\Link;
use OAT\Library\Lti1p3Core\Resource\Link\LinkInterface;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /** @var LinkInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new Link(
            'identifier',
            'url',
            [
                'icon' => ['icon'],
                'thumbnail' => ['thumbnail'],
                'embed' => 'embed',
                'window' => ['window'],
                'iframe' => ['iframe'],
            ]
        );
    }

    public function testItImplementsLinkInterface(): void
    {
        $this->assertInstanceOf(LinkInterface::class, $this->subject);
    }

    public function testPropertiesGetters(): void
    {
        $this->assertEquals('url', $this->subject->getUrl());
        $this->assertEquals(['icon'], $this->subject->getIcon());
        $this->assertEquals(['thumbnail'], $this->subject->getThumbnail());
        $this->assertEquals('embed', $this->subject->getEmbed());
        $this->assertEquals(['window'], $this->subject->getWindow());
        $this->assertEquals(['iframe'], $this->subject->getIframe());
    }
}
