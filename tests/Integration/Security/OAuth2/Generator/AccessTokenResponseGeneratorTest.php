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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\OAuth2\Generator;

use Cache\Adapter\PHPArray\ArrayCachePool;
use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Security\OAuth2\Generator\AccessTokenResponseGenerator;
use OAT\Library\Lti1p3Core\Security\OAuth2\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ScopeRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class AccessTokenResponseGeneratorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var ArrayCachePool */
    private $cache;

    /** @var KeyChainRepositoryInterface|MockObject */
    private $keyChainRepositoryMock;

    /** @var AccessTokenResponseGenerator */
    private $subject;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();

        $this->keyChainRepositoryMock = $this->createMock(KeyChainRepositoryInterface::class);

        $factory = new AuthorizationServerFactory(
            new ClientRepository($this->createTestRegistrationRepository()),
            new AccessTokenRepository($this->cache),
            new ScopeRepository(
                [
                    new Scope('scope1'),
                    new Scope('scope2')
                ]
            ),
            'encryptionKey'
        );

        $this->subject = new AccessTokenResponseGenerator($this->keyChainRepositoryMock, $factory);
    }

    public function testGenerate(): void
    {
        $keyChain = $this->generateTestKeyChain();
        $this->keyChainRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($keyChain->getIdentifier())
            ->willReturn($keyChain);

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest('POST', '/example', $this->generateCredentials($registration));

        $result = $this->subject->generate(
            $request,
            $this->createResponse(),
            $keyChain->getIdentifier()
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $resultData = json_decode((string)$result->getBody(), true);

        $this->assertEmpty($resultData['scope']);
        $this->assertEquals('Bearer', $resultData['token_type']);
        $this->assertEquals(3600, $resultData['expires_in']);

        $token = $this->parseJwt($resultData['access_token']);

        $this->assertEquals($registration->getClientId(), current($token->getClaims()->get('aud')));
        $this->assertEquals([], $token->getClaims()->get('scopes'));
    }

    public function testGenerateWithScopes(): void
    {
        $keyChain = $this->generateTestKeyChain();
        $this->keyChainRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($keyChain->getIdentifier())
            ->willReturn($keyChain);

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest(
            'POST',
            '/example',
            $this->generateCredentials($registration, ['scope1', 'scope2'])
        );

        $result = $this->subject->generate(
            $request,
            $this->createResponse(),
            $keyChain->getIdentifier()
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $resultData = json_decode((string)$result->getBody(), true);

        $this->assertEquals('scope1 scope2', $resultData['scope']);
        $this->assertEquals('Bearer', $resultData['token_type']);
        $this->assertEquals(3600, $resultData['expires_in']);

        $token = $this->parseJwt($resultData['access_token']);

        $this->assertEquals($registration->getClientId(), current($token->getClaims()->get('aud')));
        $this->assertEquals(['scope1', 'scope2'], $token->getClaims()->get('scopes'));
    }

    public function testGenerateWithPartialScopes(): void
    {
        $keyChain = $this->generateTestKeyChain();
        $this->keyChainRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($keyChain->getIdentifier())
            ->willReturn($keyChain);

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest(
            'POST',
            '/example',
            $this->generateCredentials($registration, ['scope1'])
        );

        $result = $this->subject->generate(
            $request,
            $this->createResponse(),
            $keyChain->getIdentifier()
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $resultData = json_decode((string)$result->getBody(), true);

        $this->assertEquals('scope1', $resultData['scope']);
        $this->assertEquals('Bearer', $resultData['token_type']);
        $this->assertEquals(3600, $resultData['expires_in']);

        $token = $this->parseJwt($resultData['access_token']);

        $this->assertEquals($registration->getClientId(), current($token->getClaims()->get('aud')));
        $this->assertEquals(['scope1'], $token->getClaims()->get('scopes'));
    }

    public function testGenerateWithInvalidCredentials(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('The user credentials were incorrect');

        $keyChain = $this->generateTestKeyChain();
        $this->keyChainRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($keyChain->getIdentifier())
            ->willReturn($keyChain);

        $request = $this->createServerRequest(
            'POST',
            '/example',
            [
                'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
                'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                'client_assertion' => 'invalid',
                'scope' => ''
            ]
        );

        $result = $this->subject->generate($request, $this->createResponse(), $keyChain->getIdentifier());

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    public function testGenerateWithInvalidScopes(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('The requested scope is invalid, unknown, or malformed');

        $keyChain = $this->generateTestKeyChain();
        $this->keyChainRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($keyChain->getIdentifier())
            ->willReturn($keyChain);

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest(
            'POST',
            '/example',
            $this->generateCredentials($registration, ['invalid'])
        );

        $this->subject->generate(
            $request,
            $this->createResponse(),
            $keyChain->getIdentifier()
        );
    }

    public function testGenerateWitInvalidKeyChain(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('Invalid key chain identifier');

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest(
            'POST',
            '/example',
            $this->generateCredentials($registration, ['scope1', 'scope2'])
        );

        $result = $this->subject->generate(
            $request,
            $this->createResponse(),
            'invalid'
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    private function generateTestKeyChain(): KeyChainInterface
    {
       return new KeyChain(
            'identifier',
            'setName',
            new Key(__DIR__ . '/../../../../Resource/Key/RSA/public.key'),
            new Key(__DIR__ . '/../../../../Resource/Key/RSA/private.key')
        );
    }

    private function generateCredentials(RegistrationInterface $registration, array $scopes = []): array
    {
        $clientAssertion = $this->createTestClientAssertion($registration);

        return [
            'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
            'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
            'client_assertion' => $clientAssertion,
            'scope' => implode(' ', $scopes)
        ];
    }
}
