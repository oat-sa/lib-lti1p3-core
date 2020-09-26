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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Resource\LtiResourceLink;

use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLinkInterface;
use PHPUnit\Framework\TestCase;

class LtiResourceLinkTest extends TestCase
{
    /** @var LtiResourceLinkInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new LtiResourceLink(
            'identifier',
            [
                'url' => 'url',
                'icon' => ['icon'],
                'thumbnail' => ['thumbnail'],
                'iframe' => ['iframe'],
                'custom' => ['custom'],
                'lineItem' => ['lineItem'],
                'available' => ['available'],
                'submission' => ['submission'],
            ]
        );
    }

    public function testItImplementsLtiResourceLinkInterface(): void
    {
        $this->assertInstanceOf(LtiResourceLinkInterface::class, $this->subject);
    }

    public function testPropertiesGetters(): void
    {
        $this->assertEquals('url', $this->subject->getUrl());
        $this->assertEquals(['icon'], $this->subject->getIcon());
        $this->assertEquals(['thumbnail'], $this->subject->getThumbnail());
        $this->assertEquals(['iframe'], $this->subject->getIframe());
        $this->assertEquals(['custom'], $this->subject->getCustom());
        $this->assertEquals(['lineItem'], $this->subject->getLineItem());
        $this->assertEquals(['available'], $this->subject->getAvailability());
        $this->assertEquals(['submission'], $this->subject->getSubmission());
    }
}
