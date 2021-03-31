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

namespace OAT\Library\Lti1p3Core\Security\Key;

class Key implements KeyInterface
{
    /** @var string|array */
    private $content;

    /** @var string|null */
    private $passPhrase;

    /** @var string|null */
    private $algorithm;

    public function __construct($content, ?string $passPhrase = null, ?string $algorithm = null)
    {
        $this->content = $content;
        $this->passPhrase = $passPhrase;
        $this->algorithm = $algorithm ?? self::ALG_RS256;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getPassPhrase(): ?string
    {
        return $this->passPhrase;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function isFromFile(): bool
    {
        if (!is_string($this->content)) {
            return false;
        }

        return strpos($this->content, self::FILE_PREFIX) === 0;
    }

    public function isFromArray(): bool
    {
        return is_array($this->content);
    }

    public function isFromString(): bool
    {
        if (is_string($this->content)) {
            return !$this->isFromFile();
        }

        return false;
    }
}
