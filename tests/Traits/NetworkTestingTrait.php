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

namespace OAT\Library\Lti1p3Core\Tests\Traits;

use Nyholm\Psr7\Factory\HttplugFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait NetworkTestingTrait
{
    private function createServerRequest(
        string $method,
        string $uri,
        array $params = [],
        array $headers = []
    ): ServerRequestInterface {
        $serverRequest =  (new Psr17Factory())->createServerRequest($method, $uri);

        foreach ($headers as $headerName => $headerValue) {
            $serverRequest = $serverRequest->withAddedHeader($headerName, $headerValue);
        }

        $method = strtoupper($method);

        if ($method === 'GET') {
            return $serverRequest->withQueryParams($params);
        }

        if ($method === 'POST') {
            return $serverRequest->withParsedBody($params);
        }

        return $serverRequest;
    }

    private function createResponse($content = null, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        return (new HttplugFactory())->createResponse($statusCode, null, $headers, $content);
    }
}
