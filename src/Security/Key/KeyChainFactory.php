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

class KeyChainFactory implements KeyChainFactoryInterface
{
    public function create(
        string $identifier,
        string $keySetName,
        $publicKey,
        $privateKey = null,
        ?string $privateKeyPassPhrase = null,
        string $algorithm = KeyInterface::ALG_RS256
    ): KeyChainInterface {
            $publicKey = new Key($publicKey, null, $algorithm);
            $privateKey = $privateKey !== null ? new Key($privateKey, $privateKeyPassPhrase, $algorithm) : null;

            return new KeyChain($identifier, $keySetName, $publicKey, $privateKey);
    }
}
