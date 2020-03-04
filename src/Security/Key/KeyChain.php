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
    private $id;

    /** @var string */
    private $setName;

    /** @var string */
    private $publicKey;

    /** @var string|null */
    private $privateKey;

    /** @var string|null */
    private $privateKeyPassPhrase;

    /** @var Key|null */
    private $buildPublicKey;

    /** @var Key|null */
    private $buildPrivateKey;

    public function __construct(
        string $id,
        string $setName,
        string $publicKey,
        string $privateKey = null,
        string $privateKeyPassPhrase = null
    ) {
        $this->id = $id;
        $this->setName = $setName;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->privateKeyPassPhrase = $privateKeyPassPhrase;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSetName(): string
    {
        return $this->setName;
    }

    public function getPublicKey(): Key
    {
        if (null === $this->buildPublicKey) {
            $this->buildPublicKey = new Key($this->publicKey);
        }

        return $this->buildPublicKey;
    }

    public function getPrivateKey(): ?Key
    {
        if (null === $this->privateKey) {
            return null;
        }

        if (null === $this->buildPrivateKey) {
            $this->buildPrivateKey = new Key($this->privateKey, $this->privateKeyPassPhrase);
        }

        return $this->buildPrivateKey;
    }
}
