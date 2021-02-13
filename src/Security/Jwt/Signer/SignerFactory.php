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

namespace OAT\Library\Lti1p3Core\Security\Jwt\Signer;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\MultibyteStringConverter;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

class SignerFactory
{
    /**
     * @throws LtiExceptionInterface
     */
    public function create(string $algorithm): Signer
    {
        switch ($algorithm) {
            case KeyInterface::ALG_ES256;
                return new Signer\Ecdsa\Sha256(new MultibyteStringConverter());
            case KeyInterface::ALG_ES384;
                return new Signer\Ecdsa\Sha384(new MultibyteStringConverter());
            case KeyInterface::ALG_ES512;
                return new Signer\Ecdsa\Sha512(new MultibyteStringConverter());
            case KeyInterface::ALG_HS256;
                return new Signer\Hmac\Sha256();
            case KeyInterface::ALG_HS384;
                return new Signer\Hmac\Sha384();
            case KeyInterface::ALG_HS512;
                return new Signer\Hmac\Sha512();
            case KeyInterface::ALG_RS256;
                return new Signer\Rsa\Sha256();
            case KeyInterface::ALG_RS384;
                return new Signer\Rsa\Sha384();
            case KeyInterface::ALG_RS512;
                return new Signer\Rsa\Sha512();
            default:
                throw new LtiException(sprintf('Unhandled algorithm %s', $algorithm));
        }
    }
}
