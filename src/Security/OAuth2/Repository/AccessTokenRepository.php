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

namespace OAT\Library\Lti1p3Core\Security\OAuth2\Repository;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\AccessToken;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    private const CACHE_PREFIX = 'lti1p3-service-server-access-token';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(CacheItemPoolInterface $cache, ?LoggerInterface $logger = null)
    {
        $this->cache = $cache;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new AccessToken();

        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        try {
            $item = $this->cache->getItem($this->getAccessTokenCacheKey($accessTokenEntity->getIdentifier()));

            $this->cache->save(
                $item->set($accessTokenEntity)->expiresAt($accessTokenEntity->getExpiryDateTime())
            );
        } catch (Throwable|CacheException $exception) {
            $this->logger->error('Cannot persist new access token: ' . $exception->getMessage());
        }
    }

    public function revokeAccessToken($tokenId): void
    {
        try {
            $this->cache->deleteItem($this->getAccessTokenCacheKey($tokenId));
        } catch (Throwable|CacheException $exception) {
            $this->logger->error('Cannot revoke access token: ' . $exception->getMessage());
        }
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        try {
            $item = $this->cache->getItem($this->getAccessTokenCacheKey($tokenId));

            return !$item->isHit();
        } catch (Throwable|CacheException $exception) {
            $this->logger->error('Cannot check if access token is revoked: ' . $exception->getMessage());
        }

        return true;
    }

    private function getAccessTokenCacheKey(string $identifier): string
    {
        return sprintf('%s-%s', self::CACHE_PREFIX, $identifier);
    }
}
