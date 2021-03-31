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

namespace OAT\Library\Lti1p3Core\Role\Collection;

use OAT\Library\Lti1p3Core\Role\RoleInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;

class RoleCollection
{
    /** @var RoleInterface[]|CollectionInterface */
    private $roles;

    public function __construct(array $roles = [])
    {
        $this->roles = new Collection();

        foreach ($roles as $role) {
            $this->add($role);
        }
    }

    public function all(): array
    {
        return $this->roles->all();
    }

    public function count(): int
    {
        return $this->roles->count();
    }

    public function add(RoleInterface $role): self
    {
        $this->roles->set($role->getName(), $role);

        return $this;
    }

    public function has(string $name): bool
    {
        return $this->roles->has($name);
    }

    public function get(string $name): RoleInterface
    {
        return $this->roles->getMandatory($name);
    }

    public function canFindBy(?string $type = null, ?bool $core = null): bool
    {
        return !empty($this->findBy($type, $core));
    }

    /**
     * @return RoleInterface[]
     */
    public function findBy(?string $type = null, ?bool $core = null): array
    {
        $roles = [];

        foreach ($this->roles as $name => $role) {
             $add = true;

            if (null !== $type) {
                $add = $role::getType() === $type;
            }

            if (null !== $core) {
                $add = $add && $role->isCore() === $core;
            }

            if ($add) {
                $roles[$name] = $role;
            }
        }

        return $roles;
    }
}
