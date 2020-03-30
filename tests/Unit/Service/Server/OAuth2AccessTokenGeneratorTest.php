<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Service\Server;

use League\OAuth2\Server\AuthorizationServer;
use OAT\Library\Lti1p3Core\Service\Server\OAuth2AccessTokenGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OAuth2AccessTokenGeneratorTest extends TestCase
{
    public function testItCanGenerateResponse(): void
    {
        /** @var ServerRequestInterface $request */
        $request = $this->createMock(ServerRequestInterface::class);

        /** @var ResponseInterface $response */
        $response = $this->createMock(ResponseInterface::class);

        /** @var AuthorizationServer|MockObject $authorizationServer */
        $authorizationServer = $this->createMock(AuthorizationServer::class);
        $authorizationServer->method('respondToAccessTokenRequest')->with($request, $response)->willReturn($response);

        $subject = new OAuth2AccessTokenGenerator($authorizationServer);

        $this->assertEquals($response, $subject->generate($request, $response));
    }
}
