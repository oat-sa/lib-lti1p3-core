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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Service\Server\Generator;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Generator\AccessTokenResponseGenerator;
use OAT\Library\Lti1p3Core\Service\Server\Entity\Scope;
use OAT\Library\Lti1p3Core\Service\Server\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Service\Server\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ScopeRepository;
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
            new ScopeRepository([new Scope('scope1'), new Scope('scope2')]),
            'encryptionKey'
        );

        $this->subject = new AccessTokenResponseGenerator($this->keyChainRepositoryMock, $factory);
    }

    public function testGenerate(): void
    {
        $keyChain = $this->createTestKeyChain();
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

        $token = (new Parser())->parse($resultData['access_token']);

        $this->assertEquals($registration->getClientId(), $token->getClaim('aud'));
        $this->assertEquals([], $token->getClaim('scopes'));
    }

    public function testGenerateWithScopes(): void
    {
        $keyChain = $this->createTestKeyChain();
        $this->keyChainRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($keyChain->getIdentifier())
            ->willReturn($keyChain);

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest('POST', '/example', $this->generateCredentials($registration, ['scope1', 'scope2']));

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

        $token = (new Parser())->parse($resultData['access_token']);

        $this->assertEquals($registration->getClientId(), $token->getClaim('aud'));
        $this->assertEquals(['scope1', 'scope2'], $token->getClaim('scopes'));
    }

    public function testGenerateWitInvalidCredentials(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('The user credentials were incorrect');

        $keyChain = $this->createTestKeyChain();
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
                'client_assertion' => 'invaid',
                'scope' => ''
            ]
        );

        $result = $this->subject->generate($request, $this->createResponse(), $keyChain->getIdentifier());

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401 , $result->getStatusCode());
    }

    public function testGenerateWitInvalidKeyChain(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('Invalid key chain identifier');

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest('POST', '/example', $this->generateCredentials($registration, ['scope1', 'scope2']));

        $result = $this->subject->generate(
            $request,
            $this->createResponse(),
            'invalid'
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401 , $result->getStatusCode());
    }

    private function generateCredentials(RegistrationInterface $registration, array $scopes = []): array
    {
        $now = Carbon::now();

        $clientAssertion =  (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
            ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getPreciseTimestamp()))
            ->issuedBy($registration->getTool()->getAudience())
            ->relatedTo($registration->getClientId())
            ->permittedFor($registration->getPlatform()->getOAuth2AccessTokenUrl())
            ->issuedAt($now->getTimestamp())
            ->expiresAt($now->addSeconds(MessageInterface::TTL)->getTimestamp())
            ->getToken(new Sha256(), $registration->getToolKeyChain()->getPrivateKey())
            ->__toString();

        return [
            'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
            'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
            'client_assertion' => $clientAssertion,
            'scope' => implode(' ', $scopes)
        ];
    }
}
