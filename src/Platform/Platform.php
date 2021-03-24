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

namespace OAT\Library\Lti1p3Core\Platform;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#platforms-and-tools
 */
class Platform implements PlatformInterface
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $name;

    /** @var string */
    private $audience;

    /** @var string|null */
    private $oidcAuthenticationUrl;

    /** @var string|null */
    private $oAuth2AccessTokenUrl;

    public function __construct(
        string $identifier,
        string $name,
        string $audience,
        ?string $oidcAuthenticationUrl = null,
        ?string $oAuth2AccessTokenUrl = null
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->audience = $audience;
        $this->oAuth2AccessTokenUrl = $oAuth2AccessTokenUrl;
        $this->oidcAuthenticationUrl = $oidcAuthenticationUrl;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAudience(): string
    {
        return $this->audience;
    }

    public function getOidcAuthenticationUrl(): ?string
    {
        return $this->oidcAuthenticationUrl;
    }

    public function getOAuth2AccessTokenUrl(): ?string
    {
        return $this->oAuth2AccessTokenUrl;
    }
}
