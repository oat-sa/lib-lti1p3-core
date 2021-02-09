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

use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;

class KeyChainRepository implements KeyChainRepositoryInterface
{
    /** @var CollectionInterface|KeyChainInterface[] */
    private $keyChains;

    public function __construct(array $keyChains = [])
    {
        $this->keyChains = new Collection();

        foreach ($keyChains as $keyChain) {
            $this->addKeyChain($keyChain);
        }
    }

    public function addKeyChain(KeyChainInterface $keyChain): self
    {
        $this->keyChains->set($keyChain->getIdentifier(), $keyChain);

        return $this;
    }

    public function find(string $identifier): ?KeyChainInterface
    {
        return $this->keyChains->get($identifier);
    }

    /**
     * @return KeyChainInterface[]
     */
    public function findByKeySetName(string $keySetName): array
    {
        $foundKeyChains = [];

        foreach ($this->keyChains->all() as $keyChain) {
            if ($keyChain->getKeySetName() === $keySetName) {
                $foundKeyChains[$keyChain->getIdentifier()] = $keyChain;
            }
        }

        return $foundKeyChains;
    }
}
