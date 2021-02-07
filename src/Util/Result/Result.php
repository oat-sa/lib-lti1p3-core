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

namespace OAT\Library\Lti1p3Core\Util\Result;

class Result implements ResultInterface
{
    /** @var string[] */
    private $successes;

    /** @var string[] */
    private $errors;

    public function __construct(array $successes = [], array $errors = [])
    {
        $this->successes = $successes;
        $this->errors = $errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function addSuccess(string $message): ResultInterface
    {
        $this->successes[] = $message;
    }

    public function addError(string $message): ResultInterface
    {
        $this->errors[] = $message;
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
