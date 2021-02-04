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

namespace OAT\Library\Lti1p3Core\Message\Launch\Validator;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use OAT\Library\Lti1p3Core\Security\Jwt\ConfigurationFactory;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;

abstract class AbstractLaunchValidator
{
    /** @var RegistrationRepositoryInterface */
    protected $registrationRepository;

    /** @var NonceRepositoryInterface */
    protected $nonceRepository;

    /** @var JwksFetcherInterface */
    protected $fetcher;

    /** @var ConfigurationFactory */
    protected $factory;

    /** @var string[] */
    protected $successes = [];

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        NonceRepositoryInterface $nonceRepository,
        JwksFetcherInterface $jwksFetcher = null,
        ConfigurationFactory $factory = null
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->nonceRepository = $nonceRepository;
        $this->fetcher = $jwksFetcher ?? new JwksFetcher();
        $this->factory = $factory ?? new ConfigurationFactory();
    }

    abstract public function getSupportedMessageTypes(): array;

    protected function addSuccess(string $message): self
    {
        $this->successes[] = $message;

        return $this;
    }

    protected function reset(): self
    {
        $this->successes = [];

        return $this;
    }
}
