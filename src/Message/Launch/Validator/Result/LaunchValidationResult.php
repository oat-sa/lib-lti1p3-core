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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Message\Launch\Validator\Result;

use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Util\Result\Result;

class LaunchValidationResult extends Result implements LaunchValidationResultInterface
{
    /** @var RegistrationInterface|null */
    private $registration;

    /** @var LtiMessagePayloadInterface|null */
    private $payload;

    /** @var MessagePayloadInterface|null */
    private $state;

    public function __construct(
        ?RegistrationInterface $registration = null,
        ?LtiMessagePayloadInterface $payload = null,
        ?MessagePayloadInterface $state = null,
        array $successes = [],
        ?string $error = null
    ) {
        $this->registration = $registration;
        $this->payload = $payload;
        $this->state = $state;

        parent::__construct($successes, $error);
    }

    public function getRegistration(): ?RegistrationInterface
    {
        return $this->registration;
    }

    public function getPayload(): ?LtiMessagePayloadInterface
    {
        return $this->payload;
    }

    public function getState(): ?MessagePayloadInterface
    {
        return $this->state;
    }
}
