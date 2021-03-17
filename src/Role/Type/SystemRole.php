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

namespace OAT\Library\Lti1p3Core\Role\Type;

use OAT\Library\Lti1p3Core\Role\AbstractRole;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lis-vocabulary-for-system-roles
 */
class SystemRole extends AbstractRole
{
    public static function getType(): string
    {
        return static::TYPE_SYSTEM;
    }

    public static function getNameSpace(): string
    {
        return static::NAMESPACE_SYSTEM;
    }

    protected function getMap(): array
    {
        return  [
            'Administrator' => true,
            'None'=> true,
            'AccountAdmin'=> false,
            'Creator'=> false,
            'SysAdmin'=> false,
            'SysSupport'=> false,
            'User'=> false,
        ];
    }
}
