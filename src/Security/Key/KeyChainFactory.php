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

namespace OAT\Library\Lti1p3Core\Security\Key;

use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use OAT\Library\Lti1p3Core\Collection\Collection;
use OAT\Library\Lti1p3Core\Collection\CollectionInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use Throwable;

class KeyChainFactory implements KeyChainFactoryInterface
{
    private const FILE_PREFIX = 'file://';

    /** @var CollectionInterface|Key[] */
    private $keys;

    public function __construct()
    {
        $this->keys = new Collection();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function create(
        string $identifier,
        string $keySetName,
        string $publicKey,
        string $privateKey = null,
        string $privateKeyPassPhrase = null
    ): KeyChainInterface {
        try {
            return new KeyChain(
                $identifier,
                $keySetName,
                $this->findKey($publicKey),
                $privateKey !== null ? $this->findKey($privateKey, $privateKeyPassPhrase) : null
            );
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot create key chain: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function findKey(string $content, string $passPhrase = null): Key
    {
        $identifier = sha1($content . $passPhrase);

        if (!$this->keys->has($identifier)) {
            $key = $this->createKey($content, $passPhrase);
            $this->keys->set($identifier, $key);
        }

        return $this->keys->get($identifier);
    }

    private function createKey(string $content, string $passPhrase = null): Key
    {
        if (strpos($content, self::FILE_PREFIX) === 0) {
            return LocalFileReference::file(substr($content, strlen(self::FILE_PREFIX)), $passPhrase ?? '');
        }

        if (false !== base64_decode($content)) {
            return InMemory::base64Encoded($content, $passPhrase ?? '');
        }

        return InMemory::plainText($content, $passPhrase ?? '');
    }
}
