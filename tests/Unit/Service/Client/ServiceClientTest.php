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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Service\Client;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClient;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClientInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\JwtClientCredentialsGrant;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ServiceClientTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var ArrayCachePool */
    private $cache;

    /** @var ClientInterface|MockObject */
    private $clientMock;

    /** @var ServiceClient */
    private $subject;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();
        $this->clientMock = $this->createMock(ClientInterface::class);

        $this->subject = new ServiceClient($this->cache, $this->clientMock);
    }

    public function testItCanPerformAServiceCallFromEmptyTokenCache(): void
    {
        $registration = $this->createTestRegistration();

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                    [
                        'json' => [
                            'grant_type' => ServiceClientInterface::GRANT_TYPE,
                            'client_assertion_type' => JwtClientCredentialsGrant::CLIENT_ASSERTION_TYPE,
                            'client_assertion' => $this->getTestJwtClientAssertion(),
                            'scope' => ''
                        ]
                    ]
                ],
                [
                    'GET',
                    'http://example.com',
                    [
                        'headers' => ['Authentication' => 'Bearer access_token']
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createResponse(json_encode(['access_token'=> 'access_token', 'expires_in' => 3600])),
                $this->createResponse('service response')
            );

        $result = $this->subject->request($registration, 'GET', 'http://example.com');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('service response', $result->getBody()->__toString());

        $cacheKey = sprintf('lti1p3-service-client-token-' . $registration->getIdentifier());
        $this->assertTrue($this->cache->hasItem($cacheKey));
        $this->assertEquals('access_token', $this->cache->getItem($cacheKey)->get());
    }

    public function testItCanPerformAServiceCallFromPopulatedTokenCache(): void
    {
        $registration = $this->createTestRegistration();

        $cacheKey = sprintf('lti1p3-service-client-token-' . $registration->getIdentifier());
        $cacheItem = $this->cache->getItem($cacheKey)->set('cached_access_token');
        $this->cache->save($cacheItem);

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com',
                [
                    'headers' => ['Authentication' => 'Bearer cached_access_token']
                ]
            )
            ->willReturn(
                $this->createResponse('service response')
            );

        $result = $this->subject->request($registration, 'GET', 'http://example.com');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('service response', $result->getBody()->__toString());
    }

    public function testItThrowAnLtiExceptionOnMissingToolKeyChain(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot generate credentials: Tool key chain is not configured');

        $registration = $this->createTestRegistrationWithoutToolKeyChain();

        $this->subject->request($registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseCode(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: invalid response http status code');

        $registration = $this->createTestRegistration();

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => JwtClientCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('invalid service response', 500)
            );

        $this->subject->request($registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseBodyFormat(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: json_decode error: Syntax error');

        $registration = $this->createTestRegistration();

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => JwtClientCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('invalid')
            );

        $this->subject->request($registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseBodyContent(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: invalid response body');

        $registration = $this->createTestRegistration();

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => JwtClientCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('{}')
            );

        $this->subject->request($registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnPlatformEndpointFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot perform request');

        $registration = $this->createTestRegistration();

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                    [
                        'json' => [
                            'grant_type' => ServiceClientInterface::GRANT_TYPE,
                            'client_assertion_type' => JwtClientCredentialsGrant::CLIENT_ASSERTION_TYPE,
                            'client_assertion' => $this->getTestJwtClientAssertion(),
                            'scope' => ''
                        ]
                    ]
                ],
                [
                    'GET',
                    'http://example.com',
                    [
                        'headers' => ['Authentication' => 'Bearer access_token']
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createResponse(json_encode(['access_token'=> 'access_token', 'expires_in' => 3600])),
                'invalid output'
            );

        $this->subject->request($registration, 'GET', 'http://example.com');
    }

    private function getTestJwtClientAssertion(): string
    {
        Carbon::setTestNow(Carbon::create(2000, 1, 1));

        return 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6InRvb2xLZXlDaGFpbiJ9.eyJqdGkiOiJyZWdpc3RyYXRpb25JZGVudGlmaWVyLTkuNDY2ODQ4RSsxNCIsImlzcyI6InBsYXRmb3JtQXVkaWVuY2UiLCJzdWIiOiJyZWdpc3RyYXRpb25DbGllbnRJZCIsImF1ZCI6Imh0dHA6XC9cL3BsYXRmb3JtLmNvbVwvYWNjZXNzLXRva2VuIiwiaWF0Ijo5NDY2ODQ4MDAsImV4cCI6OTQ2Njg1NDAwfQ.t5VpD-QctqxVTbfsm9fpMsMaK__3_YMGbWQEY25FpZFqxSf9NxaXgjv62xpfuGTYJIB20BHj4VToVHSK3gA9PAxrXArFKx5NYzLcnQgN3wLn7wulfkP703805xMQ5duAJJTMNZYbKVfFOEPRgay2iVUuN9EnAe7q-SSTSWgzG1FEtqkYMUM-ohDzjWYs0K7BCEpf3MEsNhg3amxoLAhC7MKJj4SNaBw46qTiTew4Nyi_143yO7niPw5KGBqm3KdBUoDDc7ot7OLOvlKKN9252jgkZdTkpalT9b5i3rbdx5npqWsFN_EmbLTdNhwnw_DHs1T0ALG-tDegnEr46-h6iQ';
    }
}
