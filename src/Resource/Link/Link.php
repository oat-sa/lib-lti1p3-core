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

namespace OAT\Library\Lti1p3Core\Resource\Link;

use OAT\Library\Lti1p3Core\Resource\Resource;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#link
 */
class Link extends Resource implements LinkInterface
{
    /** @var string */
    private $url;

    public function __construct(string $identifier, string $url, array $properties = [])
    {
        $this->url = $url;

        parent::__construct(
            $identifier,
            self::TYPE,
            array_merge($properties, ['url' => $this->url])
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIcon(): ?array
    {
        return $this->getProperties()->get('icon');
    }

    public function getThumbnail(): ?array
    {
        return $this->getProperties()->get('thumbnail');
    }

    public function getEmbed(): ?string
    {
        return $this->getProperties()->get('embed');
    }

    public function getWindow(): ?array
    {
        return $this->getProperties()->get('window');
    }

    public function getIframe(): ?array
    {
        return $this->getProperties()->get('iframe');
    }
}
