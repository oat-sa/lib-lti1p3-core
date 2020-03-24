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

namespace OAT\Library\Lti1p3Core\Launch\Validator;

use OAT\Library\Lti1p3Core\Message\LtiMessage;

class LtiLaunchRequestValidationResult
{
    /** @var LtiMessage */
    private $ltiMessage;

    /** @var string[] */
    private $successes;

    /** @var string[] */
    private $failures;

    public function __construct(LtiMessage $ltiMessage, array $successes = [], array $failures = [])
    {
        $this->ltiMessage = $ltiMessage;
        $this->successes = $successes;
        $this->failures = $failures;
    }

    public function getLtiMessage(): LtiMessage
    {
        return $this->ltiMessage;
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
