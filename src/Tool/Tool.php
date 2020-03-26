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

namespace OAT\Library\Lti1p3Core\Tool;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#platforms-and-tools
 */
class Tool implements ToolInterface
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $name;

    /** @var string|null */
    private $oidcLoginInitiationUrl;

    /** @var string|null */
    private $launchUrl;

    /** @var string|null */
    private $deepLinkLaunchUrl;

    public function __construct(
        string $identifier,
        string $name,
        string $oidcLoginInitiationUrl = null,
        string $launchUrl = null,
        string $deepLinkLaunchUrl = null
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->oidcLoginInitiationUrl = $oidcLoginInitiationUrl;
        $this->launchUrl = $launchUrl;
        $this->deepLinkLaunchUrl = $deepLinkLaunchUrl;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOidcLoginInitiationUrl(): ?string
    {
        return $this->oidcLoginInitiationUrl;
    }

    public function getLaunchUrl(): ?string
    {
        return $this->launchUrl;
    }


    public function getDeepLinkLaunchUrl(): ?string
    {
        return $this->deepLinkLaunchUrl;
    }
}
