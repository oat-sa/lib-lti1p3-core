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

namespace OAT\Library\Lti1p3Core\Message;

use Lcobucci\JWT\Token;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#json-web-token-0
 */
interface MessageInterface
{
    // TTL
    public const TTL = 600;

    // Headers
    public const HEADER_KID = 'kid';

    // Claims
    public const CLAIM_ISS = 'iss';
    public const CLAIM_SUB = 'sub';
    public const CLAIM_AUD = 'aud';
    public const CLAIM_EXP = 'exp';
    public const CLAIM_IAT = 'iat';

    // OIDC claims
    public const CLAIM_NONCE = 'nonce';
    public const CLAIM_PARAMETERS = 'parameters';

    public function getToken(): Token;

    public function getMandatoryClaim(string $claim);

    public function getClaim(string $claim, $default = null);
}
