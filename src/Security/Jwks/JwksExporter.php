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

namespace OAT\Library\Lti1p3Core\Security\Jwks;

use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;

/**
 * @see https://auth0.com/docs/tokens/concepts/jwks
 */
class JwksExporter
{
    /** @var KeyChainRepositoryInterface */
    private $repository;

    /** @var JwkExporter */
    private $exporter;

    public function __construct(KeyChainRepositoryInterface $repository, JwkExporter $exporter)
    {
        $this->repository = $repository;
        $this->exporter = $exporter;
    }

    public function export(string $setName): array
    {
        return [
            'keys' => array_map(
                function (KeyChainInterface $keyChain): array {
                    return $this->exporter->export($keyChain);
                },
                $this->repository->findBySetName($setName)
            )
        ];
    }
}
