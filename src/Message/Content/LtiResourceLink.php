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

namespace OAT\Library\Lti1p3Core\Message\Content;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0/#lti-resource-link
 */
class LtiResourceLink implements LtiResourceLinkInterface
{
    /** @var string */
    private $identifier;

    /** @var string|null */
    private $url;

    /** @var string|null */
    private $title;

    /** @var string|null */
    private $text;

    public function __construct(string $identifier, string $url = null, string $title = null, string $text = null)
    {
        $this->identifier = $identifier;
        $this->url = $url;
        $this->title = $title;
        $this->text = $text;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getText(): ?string
    {
        return $this->text;
    }
}
