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

namespace OAT\Library\Lti1p3Core\Security\Jwt\Configuration;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use OAT\Library\Lti1p3Core\Security\Jwt\Converter\KeyConverter;
use OAT\Library\Lti1p3Core\Security\Jwt\Decoder\AssociativeDecoder;
use OAT\Library\Lti1p3Core\Security\Jwt\Signer\SignerFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

class ConfigurationFactory
{
    /** @var SignerFactory */
    private $factory;

    /** @var KeyConverter */
    private $converter;

    public function __construct()
    {
        $this->factory = new SignerFactory();
        $this->converter = new KeyConverter();
    }

    public function create(?KeyInterface $signingKey = null, ?KeyInterface $verificationKey = null): Configuration
    {
        $algorithm = $this->findAlgorithm($signingKey, $verificationKey);

        $decoder = class_exists('Lcobucci\JWT\Parsing\Decoder') ? new AssociativeDecoder() : null;

        $configuration = Configuration::forAsymmetricSigner(
            $this->factory->create($algorithm),
            $this->convertKey($signingKey),
            $this->convertKey($verificationKey),
            null,
            $decoder
        );

        $configuration->setValidationConstraints(
            new ValidAt(SystemClock::fromUTC()),
            new SignedWith($configuration->signer(), $configuration->verificationKey())
        );

        return $configuration;
    }

    private function findAlgorithm(?KeyInterface $signingKey = null, ?KeyInterface $verificationKey = null): string
    {
        if (null !== $signingKey) {
            return $signingKey->getAlgorithm();
        }

        if (null !== $verificationKey) {
            return $verificationKey->getAlgorithm();
        }

        return KeyInterface::ALG_RS256;
    }

    private function convertKey(?KeyInterface $key = null): Key
    {
        if (null === $key) {
            return method_exists(InMemory::class, 'empty')
                ? InMemory::empty()
                : InMemory::plainText('');
        }

        return $this->converter->convert($key);
    }
}
