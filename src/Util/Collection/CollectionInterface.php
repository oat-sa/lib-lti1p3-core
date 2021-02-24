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
use Countable;
use IteratorAggregate;
use JsonSerializable;

interface CollectionInterface extends IteratorAggregate, Countable, JsonSerializable
{
    public function all(): array;

    public function keys(): array;

    public function replace(array $items): CollectionInterface;

    public function add(array $items): CollectionInterface;

    public function get(string $key, $defaultValue = null);

    public function getMandatory(string $key);

    public function set(string $key, $value): CollectionInterface;

    public function has(string $key): bool;

    public function remove(string $key): CollectionInterface;

    public function getIterator(): ArrayIterator;

    public function count(): int;
}
