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

namespace OAT\Library\Lti1p3Core\Util\Collection;

use ArrayIterator;
use InvalidArgumentException;

class Collection implements CollectionInterface
{
    /** @var array */
    private $items = [];

    public function all(): array
    {
        return $this->items;
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function replace(array $items): CollectionInterface
    {
        $this->items = $items;

        return $this;
    }

    public function add(array $items): CollectionInterface
    {
        $this->items = array_replace($this->items, $items);

        return $this;
    }

    public function get(string $key, $defaultValue = null)
    {
        return $this->items[$key] ?? $defaultValue;
    }

    public function getMandatory(string $key)
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(sprintf('Missing mandatory %s', $key));
        }

        return $this->items[$key];
    }

    public function set(string $key, $value): CollectionInterface
    {
        $this->items[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function remove(string $key): CollectionInterface
    {
        unset($this->items[$key]);

        return $this;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return $this->getIterator()->count();
    }

    public function jsonSerialize(): array
    {
        return array_filter($this->items);
    }
}
