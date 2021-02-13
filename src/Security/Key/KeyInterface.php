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

namespace OAT\Library\Lti1p3Core\Security\Key;

interface KeyInterface
{
    public const ALG_ES256 = 'ES256';
    public const ALG_ES384 = 'ES384';
    public const ALG_ES512 = 'ES512';
    public const ALG_HS256 = 'HS256';
    public const ALG_HS384 = 'HS384';
    public const ALG_HS512 = 'HS512';
    public const ALG_RS256 = 'RS256';
    public const ALG_RS384 = 'RS384';
    public const ALG_RS512 = 'RS512';

    public const FILE_PREFIX = 'file://';

    public function getContent();

    public function getPassPhrase(): ?string;

    public function getAlgorithm(): string;

    public function isFromFile(): bool;

    public function isFromArray(): bool;

    public function isFromString(): bool;
}
