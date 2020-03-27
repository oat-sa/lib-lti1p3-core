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
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->cache = new ArrayCachePool();

        $this->subject = new ServiceClient($this->cache, $this->clientMock);
    }

    public function testItCanPerformAServiceCallFromEmptyTokenCache(): void
    {
        $deployment = $this->createTestDeployment();

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $deployment->getPlatform()->getOAuth2AccessTokenUrl(),
                    [
                        'json' => [
                            'grant_type' => ServiceClientInterface::GRANT_TYPE,
                            'client_assertion_type' => ServiceClientInterface::CLIENT_ASSERTION_TYPE,
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

        $result = $this->subject->request($deployment, 'GET', 'http://example.com');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('service response', $result->getBody()->__toString());

        $cacheKey = sprintf('scat_' . $deployment->getIdentifier());
        $this->assertTrue($this->cache->hasItem($cacheKey));
        $this->assertEquals('access_token', $this->cache->getItem($cacheKey)->get());
    }

    public function testItCanPerformAServiceCallFromPopulatedTokenCache(): void
    {
        $deployment = $this->createTestDeployment();

        $cacheKey = sprintf('scat_' . $deployment->getIdentifier());
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

        $result = $this->subject->request($deployment, 'GET', 'http://example.com');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('service response', $result->getBody()->__toString());
    }

    public function testItThrowAnLtiExceptionOnMissingToolKeyChain(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot generate credentials: Tool key chain is not configured');

        $deployment = $this->createTestDeploymentWithoutToolKeyChain();

        $this->subject->request($deployment, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseCode(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: invalid response http status code');

        $deployment = $this->createTestDeployment();

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $deployment->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ServiceClientInterface::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('invalid service response', 500)
            );

        $this->subject->request($deployment, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseBodyFormat(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: json_decode error: Syntax error');

        $deployment = $this->createTestDeployment();

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $deployment->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ServiceClientInterface::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('invalid')
            );

        $this->subject->request($deployment, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseBodyContent(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: invalid response body');

        $deployment = $this->createTestDeployment();

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $deployment->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ServiceClientInterface::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('{}')
            );

        $this->subject->request($deployment, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnPlatformEndpointFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot perform request');

        $deployment = $this->createTestDeployment();

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $deployment->getPlatform()->getOAuth2AccessTokenUrl(),
                    [
                        'json' => [
                            'grant_type' => ServiceClientInterface::GRANT_TYPE,
                            'client_assertion_type' => ServiceClientInterface::CLIENT_ASSERTION_TYPE,
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

        $this->subject->request($deployment, 'GET', 'http://example.com');
    }

    private function getTestJwtClientAssertion(): string
    {
        Carbon::setTestNow(Carbon::create(2000, 1, 1));

        return 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6InRvb2xLZXlDaGFpbiJ9.eyJqdGkiOiJkZXBsb3ltZW50SWRlbnRpZmllci05LjQ2Njg0OEUrMTQiLCJpc3MiOiJ0b29sSWRlbnRpZmllciIsInN1YiI6ImRlcGxveW1lbnRDbGllbnRJZCIsImF1ZCI6Imh0dHA6XC9cL3BsYXRmb3JtLmNvbVwvYWNjZXNzLXRva2VuIiwiaWF0Ijo5NDY2ODQ4MDAsImV4cCI6OTQ2Njg1NDAwfQ.qpcevP_3NMWXRBfZREWRL-4Wf9c_XO8u8AZ40KqV4OClNefkpJM7iYOAkQpsWW6oBqo5-envKCgrvRvAuwqo89I018FenjX34j4gJgRrkYA6EYrzY460Szz_ENj-ORNMaj_H5ucyenr_JlLnlBwsEi96WDLmFizguFTk5oBNVrXhAv0Z6V91U8Jnn6fFurAjFzufKCFXw1Wz3TI6V2iFR7Z2Y7krBqJ1OJqoVBsMyjS6HGvO7KbyrCjyzkAVj6yqDiZeEMrbonWg3nv-QNHNTF4yCdwgkUxHLNy6Hb8Gk9M7099-TtnZFJ0ff7rzCNmtiBkCsSbKvRC8Z73rV-RP2g';
    }
}
