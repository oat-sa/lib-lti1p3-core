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

namespace OAT\Library\Lti1p3Core\Security\Jwks\Server;

use Nyholm\Psr7\Response;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * @see https://auth0.com/docs/tokens/concepts/jwks
 */
class JwksRequestHandler
{
    /** @var JwksExporter */
    private $exporter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        JwksExporter $exporter,
        ?LoggerInterface $logger = null
    ) {
        $this->exporter = $exporter;
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(string $keySetName): ResponseInterface
    {
        try {
            $body = json_encode($this->exporter->export($keySetName));

            $headers = [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($body)
            ];

            return new Response(200, $headers, $body);

        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Error during JWKS server handling: %s', $exception->getMessage()));

            return new Response(500, [], 'Internal JWKS server error');
        }
    }
}
