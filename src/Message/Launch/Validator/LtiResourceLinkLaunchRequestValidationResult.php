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

use OAT\Library\Lti1p3Core\Message\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Message\Token\MessageTokenInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;

class LtiResourceLinkLaunchRequestValidationResult
{
    /** @var RegistrationInterface|null */
    private $registration;

    /** @var LtiMessageTokenInterface|null */
    private $idToken;

    /** @var MessageTokenInterface|null */
    private $state;

    /** @var string[] */
    private $successes;

    /** @var string|null */
    private $error;

    public function __construct(
        RegistrationInterface $registration = null,
        LtiMessageTokenInterface $idToken = null,
        MessageTokenInterface $state = null,
        array $successes = [],
        string $error = null
    ) {
        $this->registration = $registration;
        $this->idToken = $idToken;
        $this->state = $state;
        $this->successes = $successes;
        $this->error = $error;
    }

    public function getRegistration(): ?RegistrationInterface
    {
        return $this->registration;
    }

    public function getIdToken(): ?LtiMessageTokenInterface
    {
        return $this->idToken;
    }

    public function getState(): ?MessageTokenInterface
    {
        return $this->state;
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
