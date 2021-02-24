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

use ArrayIterator;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;

class ResourceCollection implements ResourceCollectionInterface
{
    /** @var CollectionInterface|ResourceInterface[] */
    private $resources = [];

    public function __construct(iterable $resources = [])
    {
        $this->resources = new Collection();

        foreach ($resources as $resource) {
            $this->add($resource);
        }
    }

    public function add(ResourceInterface $resource): ResourceCollectionInterface
    {
        $this->resources->set($resource->getIdentifier(), $resource);

        return $this;
    }

    public function getByType(string $type): array
    {
        return array_filter(
            $this->resources->all(),
            static function (ResourceInterface $resource) use ($type) {
                return $resource->getType() === $type;
            }
        );
    }

    public function count(): int
    {
        return $this->resources->count();
    }

    /**
     * @return ResourceInterface[]|ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return $this->resources->getIterator();
    }

    public function normalize(): array
    {
        return array_map(
            static function(ResourceInterface $resource) {
                return $resource->normalize();
            },
            array_values($this->resources->all())
        );
    }
}
