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
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcInitiationRequestHandler;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcInitiationRequestHandlerTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var OidcInitiator|MockObject */
    private $initiatorMock;

    /** @var OidcInitiationRequestHandler */
    private $subject;

    protected function setUp(): void
    {
        $this->initiatorMock = $this->createMock(OidcInitiator::class);

        $this->subject= new OidcInitiationRequestHandler($this->initiatorMock);
    }

    public function testInitiationSuccessResponse(): void
    {
        $request = $this->createServerRequest('GET', 'http://example.com');

        $message = new LtiMessage('http://example.com', ['parameter' => 'value']);

        $this->initiatorMock
            ->expects($this->once())
            ->method('initiate')
            ->with($request)
            ->willReturn($message);

        $response = $this->subject->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://example.com?parameter=value', $response->getHeaderLine('Location'));
    }

    public function testInitiationErrorResponse(): void
    {
        $request = $this->createServerRequest('GET', 'http://example.com');

        $this->initiatorMock
            ->expects($this->once())
            ->method('initiate')
            ->with($request)
            ->willThrowException(new Exception('custom error'));

        $response = $this->subject->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal OIDC initiation server error', (string)$response->getBody());
    }
}
