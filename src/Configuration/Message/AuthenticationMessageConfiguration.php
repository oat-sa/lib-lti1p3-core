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

namespace OAT\Library\Lti1p3Core\Configuration\Message;

class AuthenticationMessageConfiguration
{
    public const ATTRIBUTE_RESPONSE_TYPE = 'response_type';
    public const ATTRIBUTE_REDIRECT_URI = 'redirect_uri';
    public const ATTRIBUTE_RESPONSE_MODE = 'response_mode';
    public const ATTRIBUTE_CLIENT_ID = 'client_id';
    public const ATTRIBUTE_SCOPE = 'scope';
    public const ATTRIBUTE_STATE = 'state';
    public const ATTRIBUTE_LOGIN_HINT = 'login_hint';
    public const ATTRIBUTE_MESSAGE_HINT = 'lti_message_hint';
    public const ATTRIBUTE_PROMPT = 'prompt';
    public const ATTRIBUTE_NONCE = 'nonce';

    public const ATTRIBUTES = [
        self::ATTRIBUTE_RESPONSE_TYPE,
        self::ATTRIBUTE_REDIRECT_URI,
        self::ATTRIBUTE_RESPONSE_MODE,
        self::ATTRIBUTE_CLIENT_ID,
        self::ATTRIBUTE_SCOPE,
        self::ATTRIBUTE_STATE,
        self::ATTRIBUTE_LOGIN_HINT,
        self::ATTRIBUTE_MESSAGE_HINT,
        self::ATTRIBUTE_PROMPT,
        self::ATTRIBUTE_NONCE
    ];
}
