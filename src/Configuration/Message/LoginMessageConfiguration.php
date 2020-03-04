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

class LoginMessageConfiguration
{
    public const ATTRIBUTE_ISSUER = 'iss';
    public const ATTRIBUTE_LOGIN_HINT = 'login_hint';
    public const ATTRIBUTE_TARGET_LINK_URI = 'target_link_uri';
    public const ATTRIBUTE_LTI_MESSAGE_HINT = 'lti_message_hint';
    public const ATTRIBUTE_LTI_DEPLOYMENT_ID = 'lti_deployment_id';
    public const ATTRIBUTE_CLIENT_ID = 'client_id';

    public const MANDATORY_ATTRIBUTES = [
        self::ATTRIBUTE_ISSUER,
        self::ATTRIBUTE_LOGIN_HINT,
        self::ATTRIBUTE_TARGET_LINK_URI
    ];

    public const OPTIONALS_ATTRIBUTES = [
        self::ATTRIBUTE_LTI_MESSAGE_HINT,
        self::ATTRIBUTE_LTI_DEPLOYMENT_ID,
        self::ATTRIBUTE_CLIENT_ID
    ];

    public const ATTRIBUTES = [
        self::MANDATORY_ATTRIBUTES,
        self::OPTIONALS_ATTRIBUTES
    ];
}
