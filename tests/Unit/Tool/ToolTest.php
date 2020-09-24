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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Tool;

use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class ToolTest extends TestCase
{
    use DomainTestingTrait;

    public function testGetters(): void
    {
        $subject = $this->createTestTool();

        $this->assertEquals('toolIdentifier', $subject->getIdentifier());
        $this->assertEquals('toolName', $subject->getName());
        $this->assertEquals('http://tool.com/oidc-init', $subject->getOidcInitiationUrl());
        $this->assertEquals('http://tool.com/launch', $subject->getLaunchUrl());
        $this->assertEquals('http://tool.com/deep-launch', $subject->getDeepLinkingUrl());
    }
}
