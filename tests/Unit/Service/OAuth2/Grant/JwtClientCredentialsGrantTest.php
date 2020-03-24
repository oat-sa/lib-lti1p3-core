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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Service\OAuth2\Grant;

use Carbon\Carbon;
use DateInterval;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Service\OAuth2\Grant\JwtClientCredentialsGrant;
use OAT\Library\Lti1p3Core\Tests\Traits\KeyChainTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class JwtClientCredentialsGrantTest extends TestCase
{
    use KeyChainTestingTrait;

    /** @var JwtClientCredentialsGrant */
    private $subject;
    
    /** @var DeploymentRepositoryInterface|MockObject */
    private $deploymentRepository;

    /** @var Builder */
    private $builder;

    /** @var KeyChainInterface */
    private $keyChain;
    
    protected function setUp()
    {
        $this->keyChain = $this->getTestingKeyChain();

        $this->deploymentRepository = $this->createMock(DeploymentRepositoryInterface::class);

        $this->subject = new JwtClientCredentialsGrant($this->deploymentRepository);
    }

    public function testItExtendsAbstractGrant(): void
    {
        $this->assertInstanceOf(AbstractGrant::class, $this->subject);
    }

    public function testItHasCorrectIdentifier(): void
    {
        $this->assertEquals('client_credentials', $this->subject->getIdentifier());
    }

    public function testItCanCheckRequestParametersCorrectly(): void
    {
        $this->assertTrue($this->subject->canRespondToAccessTokenRequest($this->createCorrectRequest()));
        $this->assertFalse($this->subject->canRespondToAccessTokenRequest($this->createWrongRequest()));
    }

    public function testItCanAddAccessTokenToResponse(): void
    {
        $this->prepareMockClasses($this->createDeployment());

        $request = $this->createCorrectRequest((string) $this->createJWT());
        /** @var ResponseTypeInterface $responseType */
        $responseType = $this->createMock(ResponseTypeInterface::class);

        $this->assertInstanceOf(ResponseTypeInterface::class, $this->subject->respondToAccessTokenRequest($request, $responseType, new DateInterval('PT1H')));
    }

    public function testItThrowsExceptionOnInvalidToken(): void
    {
        $request = $this->createCorrectRequest(null);
        /** @var ResponseTypeInterface $responseType */
        $responseType = $this->createMock(ResponseTypeInterface::class);

        $this->expectException(OAuthServerException::class);

        $this->subject->respondToAccessTokenRequest($request, $responseType, new DateInterval('PT1H'));
    }

    public function testItThrowsExceptionIfDeploymentNotFound(): void
    {
        $this->prepareMockClasses();

        $request = $this->createCorrectRequest((string) $this->createJWT());
        /** @var ResponseTypeInterface $responseType */
        $responseType = $this->createMock(ResponseTypeInterface::class);

        $this->expectException(OAuthServerException::class);

        $this->subject->respondToAccessTokenRequest($request, $responseType, new DateInterval('PT1H'));
    }

    public function testItThrowsExceptionIfTokenIsExpired(): void
    {
        $this->prepareMockClasses($this->createDeployment());

        $request = $this->createCorrectRequest((string) $this->createJWT(-1));
        /** @var ResponseTypeInterface $responseType */
        $responseType = $this->createMock(ResponseTypeInterface::class);

        $this->expectException(OAuthServerException::class);

        $this->subject->respondToAccessTokenRequest($request, $responseType, new DateInterval('PT1H'));
    }

    private function prepareMockClasses(DeploymentInterface $deployment = null): void
    {
        $this->builder = new Builder();

        $this->deploymentRepository->method('findByIssuer')->with('issuer', 'subject')->willReturn($deployment);

        /** @var ClientRepositoryInterface $clientRepository */
        $clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $clientRepository->method('getClientEntity')->willReturn($this->createMock(ClientEntityInterface::class));
        $this->subject->setClientRepository($clientRepository);

        /** @var ScopeRepositoryInterface $scopeRepository */
        $scopeRepository = $this->createMock(ScopeRepositoryInterface::class);
        $scopeRepository->method('finalizeScopes')->willReturn([]);
        $this->subject->setScopeRepository($scopeRepository);

        $accessToken = $this->createMock(AccessTokenEntityInterface::class);

        $this->subject->setPrivateKey($this->createMock(CryptKey::class));

        /** @var AccessTokenRepositoryInterface $accessTokenRepository */
        $accessTokenRepository = $this->createMock(AccessTokenRepositoryInterface::class);
        $accessTokenRepository->method('getNewToken')->willReturn($accessToken);
        $this->subject->setAccessTokenRepository($accessTokenRepository);
    }

    /**
     * @return ServerRequestInterface|MockObject
     */
    private function createCorrectRequest(string $jwtToken = null): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->method('getParsedBody')
            ->willReturn([
                'grant_type' => 'client_credentials',
                'client_assertion' => $jwtToken,
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
            ]);

        return $request;
    }

    /**
     * @return ServerRequestInterface|MockObject
     */
    private function createWrongRequest(): ServerRequestInterface
    {
        return $this->createMock(ServerRequestInterface::class);
    }

    private function createJWT(int $ttl = 3600): Token
    {
        $now = Carbon::now();

        $this->builder
            ->withClaim('iss', 'issuer')
            ->withClaim('sub', 'subject')
            ->identifiedBy(Uuid::uuid4()->toString())
            ->issuedAt($now->getTimestamp())
            ->expiresAt($now->addSeconds($ttl)->getTimestamp());

        return $this->builder->getToken(new Sha256(), $this->keyChain->getPrivateKey());
    }

    private function createDeployment(): DeploymentInterface
    {
        /** @var DeploymentInterface|MockObject $deployment */
        $deployment = $this->createMock(DeploymentInterface::class);
        $deployment->method('getToolKeyChain')->willReturn($this->keyChain);

        return $deployment;
    }
}
