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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Service\Server\Endpoint;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Server\Endpoint\RegistrationAccessTokenRequestHandler;
use OAT\Library\Lti1p3Core\Service\Server\Entity\Scope;
use OAT\Library\Lti1p3Core\Service\Server\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Service\Server\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ScopeRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RegistrationAccessTokenRequestHandlerTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var ArrayCachePool */
    private $cache;

    /** @var RegistrationAccessTokenRequestHandler */
    private $subject;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();

        $registrationRepository = $this->createTestRegistrationRepository();

        $factory = new AuthorizationServerFactory(
            new ClientRepository($registrationRepository),
            new AccessTokenRepository($this->cache),
            new ScopeRepository([new Scope('scope1'), new Scope('scope2')]),
            'encryptionKey'
        );

        $this->subject = new RegistrationAccessTokenRequestHandler($registrationRepository, $factory);
    }

    public function testHandle(): void
    {
        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest('POST', '/example', $this->generateCredentials($registration));

        $result = $this->subject->handle(
            $request,
            $this->createResponse(),
            $registration->getIdentifier()
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

    public function testHandleWithScopes(): void
    {
        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest('POST', '/example', $this->generateCredentials($registration, ['scope1', 'scope2']));

        $result = $this->subject->handle(
            $request,
            $this->createResponse(),
            $registration->getIdentifier()
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

    public function testHandleWitInvalidCredentials(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('The user credentials were incorrect');

        $registration = $this->createTestRegistration();
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

        $result = $this->subject->handle($request, $this->createResponse(), $registration->getIdentifier());

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401 , $result->getStatusCode());
    }

    public function testHandleWitInvalidRegistration(): void
    {
        $this->expectException(OAuthServerException::class);
        $this->expectExceptionMessage('Invalid registration identifier');

        $registration = $this->createTestRegistration();
        $request = $this->createServerRequest('POST', '/example', $this->generateCredentials($registration, ['scope1', 'scope2']));

        $result = $this->subject->handle(
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
