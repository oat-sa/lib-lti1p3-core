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

namespace OAT\Library\Lti1p3Core\Security\Jwt;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use OAT\Library\Lti1p3Core\Security\Jwt\Decoder\AssociativeDecoder;

class ConfigurationFactory
{
    /** @var Signer */
    private $signer;

    public function __construct(Signer $signer = null)
    {
        $this->signer = $signer ?? new Sha256();
    }

    public function create(Key $signingKey = null, Key $verificationKey = null): Configuration
    {
        $signingKey = $signingKey ?? InMemory::plainText('');
        $verificationKey = $verificationKey ?? InMemory::plainText('');

        $configuration = Configuration::forAsymmetricSigner(
            $this->signer,
            $signingKey,
            $verificationKey,
            null,
            new AssociativeDecoder()
        );

        $configuration->setValidationConstraints(
            new ValidAt(SystemClock::fromUTC()),
            new SignedWith($this->signer, $verificationKey)
        );

        return $configuration;
    }
}
