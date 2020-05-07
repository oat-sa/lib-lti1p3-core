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

namespace OAT\Library\Lti1p3Core\Service\Server\Validator;

use Lcobucci\JWT\Token;

class AccessTokenRequestValidationResult
{
    /** @var Token */
    private $token;

    /** @var string[] */
    private $successes;

    /** @var string[] */
    private $failures;

    public function __construct(Token $token, array $successes = [], array $failures = [])
    {
        $this->token = $token;
        $this->successes = $successes;
        $this->failures = $failures;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function hasFailures(): bool
    {
        return !empty($this->failures);
    }

    public function addSuccess(string $success): self
    {
        $this->successes[] = $success;

        return $this;
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function addFailure(string $failure): self
    {
        $this->failures[] = $failure;

        return $this;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }
}
