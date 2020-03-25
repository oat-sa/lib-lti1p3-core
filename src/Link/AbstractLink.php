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

namespace OAT\Library\Lti1p3Core\Link;

/**
 * @psee http://www.imsglobal.org/spec/lti/v1p3#lti-links-0
 */
abstract class AbstractLink implements LinkInterface
{
    /** @var string */
    private $identifier;

    /** @var string|null */
    private $url;

    /** @var string[] */
    private $parameters;

    public function __construct(string $identifier, string $url = null, array $parameters = [])
    {
        $this->identifier = $identifier;
        $this->url = $url;
        $this->parameters = $parameters;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $parameterName, $default = null): ?string
    {
        return $this->parameters[$parameterName] ?? $default;
    }
}
