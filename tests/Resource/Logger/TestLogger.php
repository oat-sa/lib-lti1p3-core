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

namespace OAT\Library\Lti1p3Core\Tests\Resource\Logger;

use Psr\Log\AbstractLogger;

class TestLogger extends AbstractLogger
{
    /** @var array */
    private $logs;

    public function __construct(array $logs = [])
    {
        $this->logs = $logs;
    }

    public function log($level, $message, array $context = array()): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message
        ];
    }

    public function hasLog($level, $message): bool
    {
        foreach ($this->logs as $log) {
            if ($level === $log['level'] && $message === $log['message']) {
                return true;
            }
        }

        return false;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}
