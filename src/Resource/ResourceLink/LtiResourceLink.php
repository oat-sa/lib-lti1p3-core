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

namespace OAT\Library\Lti1p3Core\Resource\ResourceLink;

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Resource\Resource;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0/#lti-resource-link
 */
class LtiResourceLink extends Resource implements LtiResourceLinkInterface
{
    public function __construct(array $properties = [])
    {
        parent::__construct(self::TYPE, $properties);
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getIdentifier(): string
    {
        return $this->getMandatoryProperty('identifier');
    }

    public function getUrl(): ?string
    {
        return $this->getProperty('url');
    }

    public function getTitle(): ?string
    {
        return $this->getProperty('title');
    }

    public function getText(): ?string
    {
        return $this->getProperty('text');
    }
}
