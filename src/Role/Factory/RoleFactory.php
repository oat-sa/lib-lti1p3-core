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

namespace OAT\Library\Lti1p3Core\Role\Factory;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Role\RoleInterface;
use OAT\Library\Lti1p3Core\Role\Type\ContextRole;
use OAT\Library\Lti1p3Core\Role\Type\InstitutionRole;
use OAT\Library\Lti1p3Core\Role\Type\SystemRole;

class RoleFactory
{
    /**
     * @throws LtiExceptionInterface
     */
    public static function create(string $name): RoleInterface
    {
        if (strpos($name, SystemRole::getNameSpace()) === 0) {
            return new SystemRole($name);
        }

        if (strpos($name, InstitutionRole::getNameSpace()) === 0) {
            return new InstitutionRole($name);
        }

        return new ContextRole($name);
    }
}
