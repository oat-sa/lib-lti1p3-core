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

namespace OAT\Library\Lti1p3Core\Service\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\BuilderInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Grant\ClientAssertionCredentialsGrant;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0#securing_web_services
 */
class LtiServiceClient implements LtiServiceClientInterface
{
    private const CACHE_PREFIX = 'lti1p3-service-client-token';

    /** @var CacheItemPoolInterface|null */
    private $cache;

    /** @var ClientInterface */
    private $client;

    /** @var BuilderInterface */
    private $builder;

    public function __construct(
        ?CacheItemPoolInterface $cache = null,
        ?ClientInterface $client = null,
        ?BuilderInterface $builder = null
    ) {
        $this->cache = $cache;
        $this->client = $client ?? new Client();
        $this->builder = $builder ?? new Builder();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function request(
        RegistrationInterface $registration,
        string $method,
        string $uri,
        array $options = [],
        array $scopes = []
    ): ResponseInterface {
        try {
            $options = array_merge_recursive(
                $options,
                [
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->getAccessToken($registration, $scopes))
                    ],
                    'http_errors' => true
                ]
            );

            try {
                return $this->client->request($method, $uri, $options);
            } catch (ClientException $exception) {
                if ($exception->getResponse()->getStatusCode() === 401) {
                    $options['headers']['Authorization'] = sprintf(
                        'Bearer %s',
                        $this->getAccessToken($registration, $scopes, true)
                    );

                    return $this->client->request($method, $uri, $options);
                }

                throw $exception;
            }
        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot perform request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function getAccessToken(
        RegistrationInterface $registration,
        array $scopes,
        bool $forceRefresh = false
    ): string {
        try {
            $cacheKey = sprintf(
                '%s-%s-%s',
                self::CACHE_PREFIX,
                $registration->getIdentifier(),
                sha1(implode('', $scopes))
            );

            if ($this->cache) {
                $item = $this->cache->getItem($cacheKey);

                if ($item->isHit()) {
                    if (!$forceRefresh) {
                        return $item->get();
                    }

                    $this->cache->deleteItem($cacheKey);
                }
            }

            $response = $this->client->request('POST', $registration->getPlatform()->getOAuth2AccessTokenUrl(), [
                'form_params' => [
                    'grant_type' => static::GRANT_TYPE,
                    'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                    'client_assertion' => $this->generateCredentials($registration),
                    'scope' => implode(' ', $scopes)
                ]
            ]);

            if (!in_array($response->getStatusCode(), [200, 201])) {
                throw new RuntimeException('invalid response http status code');
            }

            $responseData = json_decode($response->getBody()->__toString(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RuntimeException(sprintf('json_decode error: %s', json_last_error_msg()));
            }

            $accessToken = $responseData['access_token'] ?? '';
            $expiresIn = $responseData['expires_in'] ?? '';

            if (empty($accessToken) || empty($expiresIn)) {
                throw new RuntimeException('invalid response body');
            }

            if ($this->cache) {
                $item = $this->cache->getItem($cacheKey);

                $this->cache->save(
                    $item->set($accessToken)->expiresAfter($expiresIn)
                );
            }

            return $accessToken;

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        }catch (Throwable|CacheException $exception) {
            throw new LtiException(
                sprintf('Cannot get access token: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function generateCredentials(RegistrationInterface $registration): string
    {
        try {
            $toolKeyChain = $registration->getToolKeyChain();

            if (null === $toolKeyChain) {
                throw new LtiException('Tool key chain is not configured');
            }

            $token = $this->builder->build(
                [
                    MessagePayloadInterface::HEADER_KID => $toolKeyChain->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getTool()->getAudience(),
                    MessagePayloadInterface::CLAIM_SUB => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => [
                        $registration->getPlatform()->getAudience(),
                        $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                    ]
                ],
                $toolKeyChain->getPrivateKey()
            );

            return $token->toString();

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate credentials: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
