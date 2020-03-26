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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Link\ResourceLink;

use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use PHPUnit\Framework\TestCase;

class ResourceLinkTest extends TestCase
{
    /** @var ResourceLink */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new ResourceLink('identifier', 'url', 'title', 'description');
    }

    public function testGetIdentifier(): void
    {
        $this->assertEquals('identifier', $this->subject->getIdentifier());
    }

    public function testGetUrl(): void
    {
        $this->assertEquals('url', $this->subject->getUrl());
    }

    public function testGetType(): void
    {
        $this->assertEquals(ResourceLinkInterface::TYPE, $this->subject->getType());
    }

    public function testGetTitle(): void
    {
        $this->assertEquals('title', $this->subject->getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('description', $this->subject->getDescription());
    }

    public function testGetParameters(): void
    {
        $this->assertEquals(
            [
                'title' => 'title',
                'description' => 'description'
            ],
            $this->subject->getParameters()
        );
    }
}
