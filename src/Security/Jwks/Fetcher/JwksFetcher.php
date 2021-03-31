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

namespace OAT\Library\Lti1p3Core\Security\Jwks\Fetcher;

use CoderCat\JWKToPEM\JWKConverter;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

class JwksFetcher implements JwksFetcherInterface
{
    private const CACHE_PREFIX = 'lti1p3-jwks';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var ClientInterface */
    private $client;

    /** @var JWKConverter */
    private $converter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ?CacheItemPoolInterface $cache = null,
        ?ClientInterface $client = null,
        ?JWKConverter $converter = null,
        ?LoggerInterface $logger = null
    ) {
        $this->cache = $cache;
        $this->client = $client ?? new Client();
        $this->converter = $converter ?? new JWKConverter();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function fetchKey(string $jwksUrl, string $kId): KeyInterface
    {
        $jwksData = $this->fetchJwksDataFromCache($jwksUrl);

        if (null !== $jwksData && $key = $this->findKeyFromJwksData($kId, $jwksData)) {
            return $key;
        }

        $jwksData = $this->fetchJwksDataFromUrl($jwksUrl);

        if (null !== $jwksData) {
            $this->saveJwksDataInCache($jwksUrl, $jwksData);

            if ($key = $this->findKeyFromJwksData($kId, $jwksData)) {
                return $key;
            }
        }

        throw new LtiException(sprintf('Could not find key id %s from cache or url %s', $kId, $jwksUrl));
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function fetchJwksDataFromUrl(string $jwksUrl): ?array
    {
        try {
            $response = $this->client->request('GET', $jwksUrl, ['headers' => ['Accept' => 'application/json']]);

            $responseData = json_decode($response->getBody()->__toString(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RuntimeException(sprintf('json_decode error: %s', json_last_error_msg()));
            }

            return $responseData;
        } catch (Throwable $exception) {
            $message = sprintf('Cannot fetch JWKS data from url %s: %s',$jwksUrl, $exception->getMessage());

            $this->logger->error($message);

            throw new LtiException($message, $exception->getCode(), $exception);
        }
    }

    private function getJwksDataCacheKey(string $jwksUrl): string
    {
        return sprintf('%s-%s', self::CACHE_PREFIX, base64_encode($jwksUrl));
    }

    private function fetchJwksDataFromCache(string $jwksUrl): ?array
    {
        if ($this->cache) {
            try {
                $item = $this->cache->getItem($this->getJwksDataCacheKey($jwksUrl));

                if ($item->isHit()) {
                    return $item->get();
                }

                return null;
            } catch (Throwable|CacheException $exception) {
                $this->logger->error(sprintf('Cannot fetch JWKS data from cache: %s', $exception->getMessage()));
            }
        }

        return null;
    }

    private function saveJwksDataInCache(string $jwksUrl, array $jwksData): void
    {
        if ($this->cache) {
            try {
                $item = $this->cache->getItem($this->getJwksDataCacheKey($jwksUrl));

                $this->cache->save(
                    $item->set($jwksData)->expiresAfter(self::TTL)
                );
            } catch (Throwable|CacheException $exception) {
                $this->logger->error(sprintf('Cannot save JWKS data in cache: %s', $exception->getMessage()));
            }
        }
    }

    private function findKeyFromJwksData(string $kId, array $jwksData): ?KeyInterface
    {
        foreach ($jwksData['keys'] ?? [] as $data) {
            if ($data['kid'] === $kId) {
                return new Key($data);
            }
        }

        return null;
    }
}
