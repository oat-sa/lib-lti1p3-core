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

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;

abstract class AbstractLaunchValidator implements LaunchValidatorInterface
{
    /** @var RegistrationRepositoryInterface */
    protected $registrationRepository;

    /** @var NonceRepositoryInterface */
    protected $nonceRepository;

    /** @var JwksFetcherInterface */
    protected $fetcher;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var ParserInterface */
    protected $parser;

    /** @var string[] */
    protected $successes = [];

    /** @var bool */
    protected $isStateValidationRequired = true;

    /** @var bool */
    protected $isNonceValidationRequired = true;

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        NonceRepositoryInterface $nonceRepository,
        ?JwksFetcherInterface $jwksFetcher = null,
        ?ValidatorInterface $validator = null,
        ?ParserInterface $parser = null
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->nonceRepository = $nonceRepository;
        $this->fetcher = $jwksFetcher ?? new JwksFetcher();
        $this->validator = $validator ?? new Validator();
        $this->parser = $parser ?? new Parser();
    }

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

    public function isStateValidationRequired(): bool
    {
        return $this->isStateValidationRequired;
    }

    public function isNonceValidationRequired(): bool
    {
        return $this->isNonceValidationRequired;
    }
}
