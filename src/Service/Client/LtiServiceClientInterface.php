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

namespace OAT\Library\Lti1p3Core\Service\Client;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0#securing_web_services
 */
interface LtiServiceClientInterface
{
    public const GRANT_TYPE = 'client_credentials';

    public function request(
        RegistrationInterface $registration,
        string $method,
        string $uri,
        array $options = [],
        array $scopes = []
    ): ResponseInterface;
}
