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

namespace OAT\Library\Lti1p3Core\Message\Payload;

use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#json-web-token-0
 */
interface MessagePayloadInterface
{
    // Default TTL (in seconds)
    public const TTL = 600;

    // Headers
    public const HEADER_KID = 'kid';

    // Claims
    public const CLAIM_JTI = 'jti';
    public const CLAIM_ISS = 'iss';
    public const CLAIM_SUB = 'sub';
    public const CLAIM_AUD = 'aud';
    public const CLAIM_EXP = 'exp';
    public const CLAIM_IAT = 'iat';
    public const CLAIM_NBF = 'nbf';
    public const CLAIM_REGISTRATION_ID = 'registration_id';
    public const CLAIM_NONCE = 'nonce';
    public const CLAIM_PARAMETERS = 'parameters';
    public const CLAIM_USER_NAME ='name';
    public const CLAIM_USER_EMAIL = 'email';
    public const CLAIM_USER_GIVEN_NAME ='given_name';
    public const CLAIM_USER_FAMILY_NAME ='family_name';
    public const CLAIM_USER_MIDDLE_NAME ='middle_name';
    public const CLAIM_USER_LOCALE ='locale';
    public const CLAIM_USER_PICTURE ='picture';

    public const RESERVED_USER_CLAIMS = [
        self::CLAIM_SUB,
        self::CLAIM_USER_NAME,
        self::CLAIM_USER_EMAIL,
        self::CLAIM_USER_GIVEN_NAME,
        self::CLAIM_USER_FAMILY_NAME,
        self::CLAIM_USER_MIDDLE_NAME,
        self::CLAIM_USER_LOCALE,
        self::CLAIM_USER_PICTURE,
    ];

    public function getToken(): TokenInterface;

    public function getMandatoryClaim(string $claim);

    public function getClaim(string $claim, $default = null);

    public function hasClaim(string $claim): bool;
}
