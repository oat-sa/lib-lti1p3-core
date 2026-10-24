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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */


declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Security\Jwt\Converter;

use Firebase\JWT\JWK;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use Throwable;

/**
 * Converts an RSA public JWK into a PEM, in place of codercat/jwk-to-pem.
 *
 * jwk-to-pem wraps phpseclib 3, which cannot be installed alongside phpseclib 4
 * (the namespace changed), while firebase/php-jwt builds the key with OpenSSL.
 * The checks and messages mirror jwk-to-pem's so this is a drop-in swap.
 */
class JWKConverter
{
    /**
     * @throws LtiException
     */
    public function toPEM(array $jwk): string
    {
        if (!array_key_exists('e', $jwk) || !array_key_exists('n', $jwk) || !array_key_exists('kty', $jwk)) {
            throw new LtiException('This JWK cannot be converted to PEM format.');
        }

        if ($jwk['kty'] !== 'RSA') {
            throw new LtiException('RSA key type is currently only supported.');
        }

        if (array_key_exists('d', $jwk)) {
            throw new LtiException('Public key is currently only supported.');
        }

        try {
            $key = JWK::parseKey($jwk, 'RS256');
        } catch (Throwable $exception) {
            throw new LtiException(sprintf('Cannot read the converted JWK: %s', $exception->getMessage()), 0, $exception);
        }

        $details = null === $key ? false : openssl_pkey_get_details($key->getKeyMaterial());

        if (false === $details) {
            throw new LtiException(sprintf('Cannot read the converted JWK: %s', openssl_error_string()));
        }

        return $details['key'];
    }
}
