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

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#role-vocabularies
 */
interface RoleInterface
{
    public const TYPE_SYSTEM = 'system';
    public const TYPE_INSTITUTION = 'institution';
    public const TYPE_CONTEXT = 'context';

    public const NAMESPACE_SYSTEM = 'http://purl.imsglobal.org/vocab/lis/v2/system/person';
    public const NAMESPACE_INSTITUTION = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person';
    public const NAMESPACE_CONTEXT = 'http://purl.imsglobal.org/vocab/lis/v2/membership';

    public static function getType(): string;

    public static function getNamespace(): string;

    public function getName(): string;

    public function getSubName(): ?string;

    public function isCore(): bool;
}
