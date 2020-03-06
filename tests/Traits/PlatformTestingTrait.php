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

namespace OAT\Library\Lti1p3Core\Tests\Traits;

use OAT\Library\Lti1p3Core\Platform\PlatformInterface;

trait PlatformTestingTrait
{
    public function getTestingPlatform(array $parameters = []): PlatformInterface
    {
        return new class($parameters) implements PlatformInterface {
            /** @var string */
            private $name;

            /** @var string */
            private $audience;

            /** @var string */
            private $oAuth2AccessTokenUrl;

            /** @var string */
            private $oidcAuthenticationUrl;

            public function __construct($parameters)
            {
                $this->name = $parameters['getName'] ?? 'name';
                $this->audience = $parameters['getAudience'] ?? 'audience';
                $this->oAuth2AccessTokenUrl = $parameters['getOAuth2AccessTokenUrl'] ?? 'o_auth2_access_token_url';
                $this->oidcAuthenticationUrl = $parameters['getOidcAuthenticationUrl'] ?? 'oidc_authentication_url';
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getAudience(): string
            {
                return $this->audience;
            }

            public function getOAuth2AccessTokenUrl(): string
            {
                return $this->oAuth2AccessTokenUrl;
            }

            public function getOidcAuthenticationUrl(): string
            {
                return $this->oidcAuthenticationUrl;
            }
        };
    }
}
