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
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClient;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClientInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ServiceClientTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var RegistrationInterface */
    private $registration;

    /** @var ArrayCachePool */
    private $cache;

    /** @var ClientInterface|MockObject */
    private $clientMock;

    /** @var ServiceClient */
    private $subject;

    protected function setUp(): void
    {
        $this->registration = $this->createTestRegistration();

        $this->cache = new ArrayCachePool();
        $this->clientMock = $this->createMock(ClientInterface::class);

        $this->subject = new ServiceClient($this->cache, $this->clientMock);

        // Lock date for this test scope, to control time related claims
        Carbon::setTestNow('2020-01-01');
    }

    protected function tearDown()
    {
        // Release time lock, for other tests scopes
        Carbon::setTestNow();
    }

    public function testItCanPerformAServiceCallFromEmptyTokenCache(): void
    {
        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->registration->getPlatform()->getOAuth2AccessTokenUrl(),
                    [
                        'form_params' => [
                            'grant_type' => ServiceClientInterface::GRANT_TYPE,
                            'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                            'client_assertion' => $this->generateTestJwtClientAssertion(),
                            'scope' => ''
                        ]
                    ]
                ],
                [
                    'GET',
                    'http://example.com',
                    [
                        'headers' => ['Authorization' => 'Bearer access_token']
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createResponse(json_encode(['access_token'=> 'access_token', 'expires_in' => 3600])),
                $this->createResponse('service response')
            );

        $result = $this->subject->request($this->registration, 'GET', 'http://example.com');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('service response', $result->getBody()->__toString());

        $cacheKey = $this->generateAccessTokenCacheKey($this->registration);
        $this->assertTrue($this->cache->hasItem($cacheKey));
        $this->assertEquals('access_token', $this->cache->getItem($cacheKey)->get());
    }

    public function testItCanPerformAServiceCallFromPopulatedTokenCacheWithoutScopes(): void
    {
        $cacheKey = $this->generateAccessTokenCacheKey($this->registration);
        $cacheItem = $this->cache->getItem($cacheKey)->set('cached_access_token');
        $this->cache->save($cacheItem);

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com',
                [
                    'headers' => ['Authorization' => 'Bearer cached_access_token']
                ]
            )
            ->willReturn(
                $this->createResponse('service response')
            );

        $result = $this->subject->request($this->registration, 'GET', 'http://example.com');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('service response', $result->getBody()->__toString());
    }

    public function testItCanPerformAServiceCallFromPopulatedTokenCacheWithScopes(): void
    {
        $scopes = ['scope1', 'scope2'];

        $cacheKey = $this->generateAccessTokenCacheKey($this->registration, $scopes);
        $cacheItem = $this->cache->getItem($cacheKey)->set('cached_access_token');
        $this->cache->save($cacheItem);

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com',
                [
                    'headers' => ['Authorization' => 'Bearer cached_access_token']
                ]
            )
            ->willReturn(
                $this->createResponse('service response')
            );

        $result = $this->subject->request($this->registration, 'GET', 'http://example.com', [], $scopes);

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

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->registration->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'form_params' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->generateTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('invalid service response', 500)
            );

        $this->subject->request($this->registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseBodyFormat(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: json_decode error: Syntax error');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->registration->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'form_params' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->generateTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('invalid')
            );

        $this->subject->request($this->registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnInvalidAccessTokenResponseBodyContent(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get access token: invalid response body');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->registration->getPlatform()->getOAuth2AccessTokenUrl(),
                [
                    'form_params' => [
                        'grant_type' => ServiceClientInterface::GRANT_TYPE,
                        'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->generateTestJwtClientAssertion(),
                        'scope' => '',
                    ]
                ]
            )
            ->willReturn(
                $this->createResponse('{}')
            );

        $this->subject->request($this->registration, 'GET', 'http://example.com');
    }

    public function testItThrowAnLtiExceptionOnPlatformEndpointFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot perform request');

        $this->clientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->registration->getPlatform()->getOAuth2AccessTokenUrl(),
                    [
                        'form_params' => [
                            'grant_type' => ServiceClientInterface::GRANT_TYPE,
                            'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                            'client_assertion' => $this->generateTestJwtClientAssertion(),
                            'scope' => ''
                        ]
                    ]
                ],
                [
                    'GET',
                    'http://example.com',
                    [
                        'headers' => ['Authorization' => 'Bearer access_token']
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createResponse(json_encode(['access_token'=> 'access_token', 'expires_in' => 3600])),
                'invalid output'
            );

        $this->subject->request($this->registration, 'GET', 'http://example.com');
    }

    private function generateTestJwtClientAssertion(): string
    {
        $token = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $this->registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_JTI => sprintf(
                    '%s-%s',
                    $this->registration->getIdentifier(),
                    Carbon::now()->getTimestamp()
                ),
                MessagePayloadInterface::CLAIM_ISS => $this->registration->getTool()->getAudience(),
                MessagePayloadInterface::CLAIM_SUB => $this->registration->getClientId(),
                MessagePayloadInterface::CLAIM_AUD => $this->registration->getPlatform()->getAudience(),
                MessagePayloadInterface::CLAIM_IAT => Carbon::now()->getTimestamp(),
                MessagePayloadInterface::CLAIM_EXP => Carbon::now()->addSeconds(MessagePayloadInterface::TTL)->getTimestamp(),
            ],
            $this->registration->getToolKeyChain()->getPrivateKey()
        );

        return $token->__toString();
    }

    private function generateAccessTokenCacheKey(RegistrationInterface $registration, array $scopes = []): string
    {
        return sprintf(
            '%s-%s-%s',
            'lti1p3-service-client-token',
            $registration->getIdentifier(),
            sha1(implode('', $scopes))
        );
    }
}
