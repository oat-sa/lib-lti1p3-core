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

namespace OAT\Library\Lti1p3Core\Security\Nonce;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class NonceRepository implements NonceRepositoryInterface
{
    private const CACHE_PREFIX = 'lti1p3-nonce';

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function find(string $value): ?NonceInterface
    {
        $item = $this->cache->getItem($this->getNonceCacheKey($value));

        return $item->isHit() ? new Nonce($item->get()) : null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function save(NonceInterface $nonce): void
    {
        $item = $this->cache->getItem($this->getNonceCacheKey($nonce->getValue()));

        $this->cache->save(
            $item->set($nonce->getValue())->expiresAt($nonce->getExpiredAt())
        );
    }

    /**
     * PSR-6 reserves {}()/\@: in a key and only guarantees support up to 64 characters. A nonce is
     * supplied by the platform, so it can carry either problem. Hashing settles both at once: the
     * digest is fixed length, so the key is always 56 characters whatever arrives, and base64url
     * of it uses none of the reserved characters.
     */
    private function getNonceCacheKey(string $value): string
    {
        return sprintf(
            '%s-%s',
            self::CACHE_PREFIX,
            rtrim(strtr(base64_encode(hash('sha256', $value, true)), '+/', '-_'), '=')
        );
    }
}
