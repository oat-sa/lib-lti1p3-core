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

namespace OAT\Library\Lti1p3Core\Security\Jwks\Exporter;

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkExporterInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkRS256Exporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;

/**
 * @see https://auth0.com/docs/tokens/concepts/jwks
 */
class JwksExporter
{
    /** @var KeyChainRepositoryInterface */
    private $repository;

    /** @var JwkExporterInterface */
    private $exporter;

    public function __construct(KeyChainRepositoryInterface $repository, ?JwkExporterInterface $exporter = null)
    {
        $this->repository = $repository;
        $this->exporter = $exporter ?? new JwkRS256Exporter();
    }

    public function export(string $keySetName): array
    {
        return [
            'keys' => array_map(
                function (KeyChainInterface $keyChain): array {
                    return $this->exporter->export($keyChain);
                },
                array_values($this->repository->findByKeySetName($keySetName))
            )
        ];
    }
}
