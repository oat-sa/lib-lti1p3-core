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

namespace OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk;

use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use const OPENSSL_KEYTYPE_RSA;

/**
 * @see https://tools.ietf.org/html/rfc7517
 */
class JwkRS256Exporter implements JwkExporterInterface
{
    public function export(KeyChainInterface $keyChain): array
    {
        $details = openssl_pkey_get_details(
            openssl_pkey_get_public($keyChain->getPublicKey()->getContent())
        );

        if ($details['type'] !== OPENSSL_KEYTYPE_RSA) {
            throw new InvalidArgumentException('Public key type is not OPENSSL_KEYTYPE_RSA');
        }

        return [
            'alg' => 'RS256',
            'kty' => 'RSA',
            'use' => 'sig',
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
            'kid' => $keyChain->getIdentifier(),
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return str_replace('=', '', strtr(base64_encode($value), '+/', '-_'));
    }
}
