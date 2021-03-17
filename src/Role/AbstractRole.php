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

namespace OAT\Library\Lti1p3Core\Role;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;

abstract class AbstractRole implements RoleInterface
{
    /** @var string */
    protected $name;

    /**
     * @throws LtiExceptionInterface
     */
    public function __construct(string $name)
    {
        $this->name = $name;

        if (!$this->isValid()) {
            throw new LtiException(sprintf('Role %s is invalid for type %s', $this->name, static::getType()));
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubName(): ?string
    {
        return null;
    }

    public function isCore(): bool
    {
        $exp = explode('#', $this->name);

        return $this->getMap()[end($exp)];
    }

    protected function isValid(): bool
    {
        if (strpos($this->name, static::getNamespace()) !== 0) {
            return false;
        }

        $exp = explode('#', $this->name);

        return array_key_exists(end($exp), $this->getMap());
    }

    abstract protected function getMap(): array;
}
