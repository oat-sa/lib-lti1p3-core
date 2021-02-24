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

namespace OAT\Library\Lti1p3Core\Resource;

use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;

class Resource implements ResourceInterface
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $type;

    /** @var CollectionInterface */
    private $properties;

    public function __construct(string $identifier, string $type, array $properties = [])
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->properties = (new Collection())->add($properties);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->getProperties()->get('title');
    }

    public function getText(): ?string
    {
        return $this->getProperties()->get('text');
    }

    public function getProperties(): CollectionInterface
    {
        return $this->properties;
    }

    public function normalize(): array
    {
        return array_filter(
            array_merge(
                $this->properties->all(),
                ['type' => $this->type]
            )
        );
    }
}
