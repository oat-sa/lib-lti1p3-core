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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message;

use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiMessageTest extends TestCase
{
    use NetworkTestingTrait;

    /** @var LtiMessage */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LtiMessage(
            'http://example.com',
            [
                'parameter' => 'value',
            ]
        );
    }

    public function testGetUrl(): void
    {
        $this->assertEquals('http://example.com', $this->subject->getUrl());
    }

    public function testGetParameter(): void
    {
        $this->assertEquals('value', $this->subject->getParameters()->get('parameter'));

        $this->assertNull($this->subject->getParameters()->get('invalid'));
        $this->assertEquals('default', $this->subject->getParameters()->get('invalid', 'default'));
    }

    public function testHasParameter(): void
    {
        $this->assertTrue($this->subject->getParameters()->has('parameter'));
        $this->assertFalse($this->subject->getParameters()->has('invalid'));
    }

    public function testGetMandatoryParameter(): void
    {
        $this->assertEquals('value', $this->subject->getParameters()->getMandatory('parameter'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing mandatory invalid');

        $this->subject->getParameters()->getMandatory('invalid');
    }

    public function testFromGetServerRequest(): void
    {
        $request = $this->createServerRequest(
            'GET',
            'http://example.com?parameter=value'
        );

        $subject = LtiMessage::fromServerRequest($request);

        $this->assertInstanceOf(LtiMessage::class, $subject);
        $this->assertEquals('http://example.com?parameter=value', $subject->getUrl());
        $this->assertEquals('value', $subject->getParameters()->get('parameter'));
    }

    public function testFromPostServerRequest(): void
    {
        $request = $this->createServerRequest(
            'POST',
            'http://example.com',
            [
                'parameter' => 'value',
            ]
        );

        $subject = LtiMessage::fromServerRequest($request);

        $this->assertInstanceOf(LtiMessage::class, $subject);
        $this->assertEquals('http://example.com', $subject->getUrl());
        $this->assertEquals('value', $subject->getParameters()->get('parameter'));
    }

    public function testFromInvalidServerRequest(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('Unsupported request method PUT');

        $request = $this->createServerRequest('PUT', 'http://example.com');

        LtiMessage::fromServerRequest($request);
    }

    public function testToUrl(): void
    {
        $this->assertEquals('http://example.com?parameter=value', $this->subject->toUrl());
    }

    public function testToUrlWithExistingQueryParameters(): void
    {
        $subject = new LtiMessage(
            'http://example.com?existingParameter=existingValue',
            [
                'newParameter' => 'newValue',
            ]
        );

        $this->assertEquals('http://example.com?existingParameter=existingValue&newParameter=newValue', $subject->toUrl());
    }

    public function testToHtmlLink(): void
    {
        $this->assertEquals(
            '<a href="http://example.com?parameter=value" target="_blank">title</a>',
            $this->subject->toHtmlLink('title', ['target' => '_blank'])
        );
    }

    public function testToHtmlRedirectForm(): void
    {
        $this->assertEquals(
            '<form id="launch_0e7d4b4c23fd7b077aba0602246820ff" action="http://example.com" method="POST"><input type="hidden" name="parameter" value="value"/></form><script>document.getElementById("launch_0e7d4b4c23fd7b077aba0602246820ff").submit();</script>',
            $this->subject->toHtmlRedirectForm()
        );
    }

    public function testToHtmlRedirectFormWithoutAutoSubmit(): void
    {
        $this->assertEquals(
            '<form id="launch_0e7d4b4c23fd7b077aba0602246820ff" action="http://example.com" method="POST"><input type="hidden" name="parameter" value="value"/></form>',
            $this->subject->toHtmlRedirectForm(false)
        );
    }
}
