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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Launch\Request;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Request\LtiMessage;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiLaunchRequestTest extends TestCase
{
    use NetworkTestingTrait;

    /** @var LtiMessage */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LtiMessage('http://example.com', [
            'id_token' => 'id_token',
            'state' => 'state'
        ]);
    }

    public function testGetUrl(): void
    {
        $this->assertEquals('http://example.com', $this->subject->getUrl());
    }

    public function testGetLtiMessage(): void
    {
        $this->assertEquals('id_token', $this->subject->getLtiMessage());
    }

    public function testGetOidcSate(): void
    {
        $this->assertEquals('state', $this->subject->getOidcState());
    }

    public function testGetParameterWithDefaultValue(): void
    {
        $this->assertNull($this->subject->getParameter('parameter'));
    }

    public function testGetParameterWithGivenValue(): void
    {
        $this->assertEquals('value', $this->subject->getParameter('parameter', 'value'));
    }

    public function testGetMandatoryParameterSuccess(): void
    {
        $this->assertEquals('id_token', $this->subject->getMandatoryParameter('id_token'));
    }

    public function testGetMandatoryParameterFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Mandatory parameter invalid cannot be found');

        $this->subject->getMandatoryParameter('invalid');
    }

    public function testFromGetServerRequest(): void
    {
        $request = $this->createServerRequest('GET', 'http://example.com?id_token=id_token&state=state');

        $subject = LtiMessage::fromServerRequest($request);

        $this->assertInstanceOf(LtiMessage::class, $subject);
        $this->assertEquals('http://example.com?id_token=id_token&state=state', $subject->getUrl());
        $this->assertEquals('id_token', $subject->getLtiMessage());
        $this->assertEquals('state', $subject->getOidcState());
    }

    public function testFromPostServerRequest(): void
    {
        $request = $this->createServerRequest('POST', 'http://example.com', [
            'id_token' => 'id_token',
            'state' => 'state'
        ]);

        $subject = LtiMessage::fromServerRequest($request);

        $this->assertInstanceOf(LtiMessage::class, $subject);
        $this->assertEquals('http://example.com', $subject->getUrl());
        $this->assertEquals('id_token', $subject->getLtiMessage());
        $this->assertEquals('state', $subject->getOidcState());
    }

    public function testFromInvalidServerRequest(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Unsupported request method PUT');

        $request = $this->createServerRequest('PUT', 'http://example.com');

        LtiMessage::fromServerRequest($request);
    }

    public function testToUrl(): void
    {
        $this->assertEquals('http://example.com?id_token=id_token&state=state', $this->subject->toUrl());
    }

    public function testToHtmlLink(): void
    {
        $this->assertEquals(
            '<a href="http://example.com?id_token=id_token&state=state" target="_blank">title</a>',
            $this->subject->toHtmlLink('title', ['target' => '_blank'])
        );
    }

    public function testToHtmlRedirectForm(): void
    {
        $this->assertEquals(
            '<form id="launch_53b7ef0e8be08b48baf20f89d1aae43d" action="http://example.com" method="POST"><input type="hidden" name="id_token" value="id_token"/><input type="hidden" name="state" value="state"/></form><script>document.getElementById("launch_53b7ef0e8be08b48baf20f89d1aae43d").submit();</script>',
            $this->subject->toHtmlRedirectForm()
        );
    }

    public function testToHtmlRedirectFormWithoutAutoSubmit(): void
    {
        $this->assertEquals(
            '<form id="launch_53b7ef0e8be08b48baf20f89d1aae43d" action="http://example.com" method="POST"><input type="hidden" name="id_token" value="id_token"/><input type="hidden" name="state" value="state"/></form>',
            $this->subject->toHtmlRedirectForm(false)
        );
    }
}
