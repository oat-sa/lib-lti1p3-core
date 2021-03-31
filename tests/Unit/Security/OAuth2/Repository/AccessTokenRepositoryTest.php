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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\OAuth2\Repository;

use Exception;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\AccessToken;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Client;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Tests\Resource\Logger\TestLogger;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LogLevel;

class AccessTokenRepositoryTest extends TestCase
{
    use DomainTestingTrait;

    /** @var CacheItemPoolInterface|MockObject */
    private $cacheMock;

    /** @var TestLogger */
    private $logger;

    /** @var AccessTokenRepository */
    private $subject;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $this->logger = new TestLogger();

        $this->subject = new AccessTokenRepository($this->cacheMock, $this->logger);
    }

    public function testItLogsPersistFailure(): void
    {
        $accessToken = $this->createAccessToken();

        $this->cacheMock
            ->expects($this->once())
            ->method('getItem')
            ->with('lti1p3-service-server-access-token-' . $accessToken->getIdentifier())
            ->willThrowException(new Exception('cache error'));

        $this->subject->persistNewAccessToken($accessToken);

        $this->logger->hasLog(LogLevel::ERROR, 'Cannot persist new access token: cache error');
    }

    public function testItLogsRevokeFailure(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('deleteItem')
            ->with('lti1p3-service-server-access-token-tokenId')
            ->willThrowException(new Exception('cache error'));

        $this->subject->revokeAccessToken('tokenId');

        $this->logger->hasLog(LogLevel::ERROR, 'Cannot revoke access token: cache error');
    }

    public function testItLogsRevokeCheckFailure(): void
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('getItem')
            ->with('lti1p3-service-server-access-token-tokenId')
            ->willThrowException(new Exception('cache error'));

        $this->subject->isAccessTokenRevoked('tokenId');

        $this->logger->hasLog(LogLevel::ERROR, 'Cannot check if access token is revoked: cache error');
    }

    private function createAccessToken($accessTokenIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new AccessToken();

        $accessToken->setIdentifier($accessTokenIdentifier ?? 'accessTokenIdentifier');
        $accessToken->setClient(new Client($this->createTestRegistration()));
        $accessToken->addScope(new Scope('scope1'));
        $accessToken->addScope(new Scope('scope2'));
        $accessToken->setUserIdentifier('userIdentifier');

        return $accessToken;
    }
}
