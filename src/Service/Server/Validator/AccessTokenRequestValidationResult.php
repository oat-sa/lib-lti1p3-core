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

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;

class AccessTokenRequestValidationResult
{
    /** @var RegistrationInterface|null */
    private $registration;

    /** @var TokenInterface|null */
    private $token;

    /** @var string[] */
    private $successes;

    /** @var string|null */
    private $error;

    public function __construct(
        RegistrationInterface $registration = null,
        TokenInterface $token = null,
        array $successes = [],
        string $error = null
    ) {
        $this->registration = $registration;
        $this->token = $token;
        $this->successes = $successes;
        $this->error = $error;
    }

    public function getRegistration(): ?RegistrationInterface
    {
        return $this->registration;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    public function getScopes(): array
    {
        return $this->token->getClaims()->get('scopes', []);
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

    public function hasError(): bool
    {
        return null !== $this->error;
    }

    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
