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

namespace OAT\Library\Lti1p3Core\Security\Jwks\Fetcher;

use CoderCat\JWKToPEM\JWKConverter;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Lcobucci\JWT\Signer\Key;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use RuntimeException;
use Throwable;

class JwksFetcher implements JwksFetcherInterface
{
    /** @var ClientInterface */
    private $client;

    /** @var JWKConverter */
    private $converter;

    public function __construct(ClientInterface $client = null, JWKConverter $converter = null)
    {
        $this->client = $client ?? new Client();
        $this->converter = $converter ?? new JWKConverter();
    }

    /**
     * @throws LtiException
     */
    public function fetchKey(string $jwksUrl, string $kId): Key
    {
        try {
            $response = $this->client->request('GET', $jwksUrl, ['headers' => ['Accept' => 'application/json']]);

            $responseData = json_decode($response->getBody()->__toString(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RuntimeException(sprintf('json_decode error: %s', json_last_error_msg()));
            }

            foreach ($responseData['keys'] as $data) {
                if ($data['kid'] === $kId) {
                    return new Key($this->converter->toPEM($data));
                }
            }

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Error during fetching JWK for url %s: %s', $jwksUrl, $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }

        throw new LtiException(sprintf('Could not find key id %s from url %s', $kId, $jwksUrl));
    }
}
