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

use DateInterval;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use OAT\Library\Lti1p3Core\Security\Jwt\Converter\KeyConverter;
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

        $configuration = Configuration::forAsymmetricSigner(
            $this->factory->create($algorithm),
            $this->convertKey($signingKey),
            $this->convertKey($verificationKey),
            new JoseEncoder(),
            new JoseEncoder(),
        );

        $configuration->setValidationConstraints(
            new LooseValidAt(SystemClock::fromUTC(), new DateInterval('PT1S')),
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
            return InMemory::plainText('empty', 'empty');
        }

        return $this->converter->convert($key);
    }
}
