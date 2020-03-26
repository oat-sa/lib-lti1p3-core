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

class KeyChain implements KeyChainInterface
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $keySetName;

    /** @var string */
    private $publicKey;

    /** @var string|null */
    private $privateKey;

    /** @var string|null */
    private $privateKeyPassPhrase;

    /** @var Key|null */
    private $cachedPublicKey;

    /** @var Key|null */
    private $cachedPrivateKey;

    public function __construct(
        string $identifier,
        string $keySetName,
        string $publicKey,
        string $privateKey = null,
        string $privateKeyPassPhrase = null
    ) {
        $this->identifier = $identifier;
        $this->keySetName = $keySetName;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->privateKeyPassPhrase = $privateKeyPassPhrase;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getKeySetName(): string
    {
        return $this->keySetName;
    }

    public function getPublicKey(): Key
    {
        if (null === $this->cachedPublicKey) {
            $this->cachedPublicKey = new Key($this->publicKey);
        }

        return $this->cachedPublicKey;
    }

    public function getPrivateKey(): ?Key
    {
        if (null === $this->privateKey) {
            return null;
        }

        if (null === $this->cachedPrivateKey) {
            $this->cachedPrivateKey = new Key($this->privateKey, $this->privateKeyPassPhrase);
        }

        return $this->cachedPrivateKey;
    }
}
