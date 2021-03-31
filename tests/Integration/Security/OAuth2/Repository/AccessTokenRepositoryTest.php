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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\OAuth2\Repository;

use Cache\Adapter\PHPArray\ArrayCachePool;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\AccessToken;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Client;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class AccessTokenRepositoryTest extends TestCase
{
    use DomainTestingTrait;

    /** @var ArrayCachePool */
    private $cache;

    /** @var AccessTokenRepository */
    private $subject;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();

        $this->subject = new AccessTokenRepository($this->cache);
    }

    public function testGetNewToken(): void
    {
        $client = new Client($this->createTestRegistration());
        $scope1 = new Scope('scope1');
        $scope2 = new Scope('scope2');

        $result = $this->subject->getNewToken($client, [$scope1, $scope2], 'userIdentifier');

        $this->assertInstanceOf(AccessTokenEntityInterface::class, $result);

        $this->assertEquals($client, $result->getClient());
        $this->assertEquals([$scope1, $scope2], $result->getScopes());
        $this->assertEquals('userIdentifier', $result->getUserIdentifier());
    }

    public function testPersistNewAccessToken(): void
    {
        $accessToken = $this->createAccessToken();

        $this->subject->persistNewAccessToken($accessToken);

        $cacheKey = 'lti1p3-service-server-access-token-' . $accessToken->getIdentifier();

        $this->assertTrue($this->cache->has($cacheKey));
        $this->assertEquals($accessToken, $this->cache->get($cacheKey));
    }

    public function testRevokeAccessToken(): void
    {
        $accessToken1 = $this->createAccessToken('token1');
        $accessToken2 = $this->createAccessToken('token2');

        $this->subject->persistNewAccessToken($accessToken1);
        $this->subject->persistNewAccessToken($accessToken2);

        $this->subject->revokeAccessToken('token2');

        $this->assertFalse($this->subject->isAccessTokenRevoked('token1'));
        $this->assertTrue($this->subject->isAccessTokenRevoked('token2'));
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
