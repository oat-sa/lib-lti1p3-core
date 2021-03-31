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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\OAuth2\ResponseType;

use Carbon\Carbon;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use Nyholm\Psr7\Response;
use OAT\Library\Lti1p3Core\Security\OAuth2\ResponseType\ScopedBearerTokenResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScopedBearerTokenResponseTest extends TestCase
{
    /** @var ScopedBearerTokenResponse */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new ScopedBearerTokenResponse();
    }

    public function testItExtendsBearerTokenResponse(): void
    {
        $this->assertInstanceOf(BearerTokenResponse::class, $this->subject);
    }

    public function testItAddScopeToResponse(): void
    {
        $scope = $this->createMock(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn('scope');

        /** @var AccessTokenEntityInterface|MockObject $accessToken */
        $accessToken = $this->createMock(AccessTokenEntityInterface::class);
        $accessToken->method('getExpiryDateTime')->willReturn(Carbon::now());
        $accessToken->method('getScopes')->willReturn([$scope]);

        $this->subject->setAccessToken($accessToken);

        $response = $this->subject->generateHttpResponse(new Response());

        $response->getBody()->rewind();

        $responseBody = json_decode($response->getBody()->read($response->getBody()->getSize()), true);

        $this->assertEquals('scope', $responseBody['scope']);
    }
}
