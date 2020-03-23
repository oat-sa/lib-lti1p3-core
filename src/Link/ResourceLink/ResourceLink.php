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

namespace OAT\Library\Lti1p3Core\Link\ResourceLink;

use OAT\Library\Lti1p3Core\Link\AbstractLink;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#resource-link-0
 */
class ResourceLink extends AbstractLink implements ResourceLinkInterface
{
    public function __construct(string $identifier, string $url = null, string $title = null, string $description = null)
    {
        parent::__construct($identifier, static::TYPE, $url, [
            'title' => $title,
            'description' => $description
        ]);
    }

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getTitle(): ?string
    {
        return $this->getParameter('title');
    }

    public function getDescription(): ?string
    {
        return $this->getParameter('description');
    }
}
