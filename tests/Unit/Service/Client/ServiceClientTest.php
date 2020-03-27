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
        Carbon::setTestNow(Carbon::create(2000, 1, 1));

        $deployment = $this->createTestDeployment();

        $map = [
            [
                // inputs
                'POST',
                $deployment->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'json' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ServiceClientInterface::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->getTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ],
                // output
                $this->createResponse(json_encode(['access_token'=> 'access_token', 'expires_in' => 3600]))
            ],
            [
                // inputs
                'GET',
                'http://example.com',
                [
                    'headers' => ['Authentication' => 'Bearer access_token']
                ],
                // output
                $this->createResponse('service response')
            ]
        ];

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->will($this->returnValueMap($map));

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

    private function getTestJwtClientAssertion(): string
    {
        // JWT for Carbon::create(2000, 1, 1)
        return 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6InRvb2xLZXlDaGFpbiJ9.eyJqdGkiOiJkZXBsb3ltZW50SWRlbnRpZmllci05LjQ2NjgxMkUrMTQiLCJpc3MiOiJ0b29sSWRlbnRpZmllciIsInN1YiI6ImRlcGxveW1lbnRDbGllbnRJZCIsImF1ZCI6Imh0dHA6XC9cL3BsYXRmb3JtLmNvbVwvYWNjZXNzLXRva2VuIiwiaWF0Ijo5NDY2ODEyMDAsImV4cCI6OTQ2NjgxODAwfQ.uNMPKI0fMSkWKUAAwcXCKfJGah5Tj-JiS-cgF1Hrbemozkf31TCtxc5u60Dcs_6RjzKigbq06PXXQL_xaNHYvGXeaV0nQF2-m_2DBxhiKD1jApAB8urLdHVBtBG52cM3uiR6-gfJztTDcpuwLYiAqjo-Q1G2VDihWaPRoKveANIrqpm0q1-LDQOagcj8VjOTjSJnl8A7HzcuKSUUhZl7TnY1v7anqLKkS4PAqlHao1xZqnBXVDCvwEoA9SPrm2ne0hA_PcQyPT_1YXqfXWGSKi1WkvJTZWLl1RbdE9Xc-9-a9oBQkhWza0IUCkn_zGWOvJ2isEKRSU0s3OeTGRe6ew';
    }
}
