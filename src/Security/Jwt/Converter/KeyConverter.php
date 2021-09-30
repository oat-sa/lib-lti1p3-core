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

namespace OAT\Library\Lti1p3Core\Security\Jwt\Converter;

use CoderCat\JWKToPEM\JWKConverter;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;
use Throwable;

class KeyConverter
{
    /** @var JWKConverter */
    private $converter;

    /** @var CollectionInterface|Key[] */
    private $collection;

    public function __construct(?JWKConverter $converter = null)
    {
        $this->converter = $converter ?? new JWKConverter();
        $this->collection = new Collection();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function convert(KeyInterface $key): Key
    {
        try {
            $keyIdentifier = sha1($key->isFromArray() ? json_encode($key->getContent()): $key->getContent());

            if ($this->collection->has($keyIdentifier)) {
                return $this->collection->get($keyIdentifier);
            }

            $convertedKey = $this->executeConversion($key);
            $this->collection->set($keyIdentifier, $convertedKey);

            return $convertedKey;

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot convert into key: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function executeConversion(KeyInterface $key): Key
    {
        if ($key->isFromArray()) {
            return InMemory::plainText($this->converter->toPEM($key->getContent()), $key->getPassPhrase() ?? '');
        }

        if ($key->isFromFile()) {
            return InMemory::file(
                substr($key->getContent(), strlen(KeyInterface::FILE_PREFIX)),
                $key->getPassPhrase() ?? ''
            );
        }

        return InMemory::plainText($key->getContent(), $key->getPassPhrase() ?? '');
    }
}
