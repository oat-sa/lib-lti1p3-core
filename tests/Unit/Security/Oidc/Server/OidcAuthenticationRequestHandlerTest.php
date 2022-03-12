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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Oidc\Server;

use Exception;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcAuthenticationRequestHandler;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcAuthenticationRequestHandlerTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var OidcAuthenticator|MockObject */
    private $authenticatorMock;

    /** @var OidcAuthenticationRequestHandler */
    private $subject;

    protected function setUp(): void
    {
        $this->authenticatorMock = $this->createMock(OidcAuthenticator::class);

        $this->subject= new OidcAuthenticationRequestHandler($this->authenticatorMock);
    }

    public function testAuthenticationSuccessResponse(): void
    {
        $request = $this->createServerRequest('GET', 'http://example.com');

        $message = new LtiMessage('http://example.com', ['parameter' => 'value']);

        $this->authenticatorMock
            ->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($message);

        $response = $this->subject->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            '<form id="launch_0e7d4b4c23fd7b077aba0602246820ff" action="http://example.com" method="POST"><input type="hidden" name="parameter" value="value"/></form><script>window.onload=function(){document.getElementById("launch_0e7d4b4c23fd7b077aba0602246820ff").submit()}</script>',
            (string)$response->getBody()
        );
    }

    public function testInitiationErrorResponse(): void
    {
        $request = $this->createServerRequest('GET', 'http://example.com');

        $this->authenticatorMock
            ->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willThrowException(new Exception('custom error'));

        $response = $this->subject->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal OIDC authentication server error', (string)$response->getBody());
    }
}
