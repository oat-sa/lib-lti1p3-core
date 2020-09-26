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

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0#securing_web_services
 */
class ServiceClient implements ServiceClientInterface
{
    private const CACHE_PREFIX = 'lti1p3-service-client-token';

    /** @var CacheItemPoolInterface|null */
    private $cache;

    /** @var ClientInterface */
    private $client;

    /** @var Signer */
    private $signer;

    /** @var Builder */
    private $builder;

    public function __construct(
        CacheItemPoolInterface $cache = null,
        ClientInterface $client = null,
        Signer $signer = null,
        Builder $builder = null
    ) {
        $this->cache = $cache;
        $this->client = $client ?? new Client();
        $this->signer = $signer ?? new Sha256();
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
            $options = array_merge_recursive($options, [
                'headers' => ['Authorization' => sprintf('Bearer %s', $this->getAccessToken($registration, $scopes))]
            ]);

            return $this->client->request($method, $uri, $options);

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
    private function getAccessToken(RegistrationInterface $registration, array $scopes): string
    {
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
                    return $item->get();
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

            if ($response->getStatusCode() !== 200) {
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
            if (null === $registration->getToolKeyChain()) {
                throw new LtiException('Tool key chain is not configured');
            }

            $now = Carbon::now();

            return $this->builder
                ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
                ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getTimestamp()))
                ->issuedBy($registration->getTool()->getAudience())
                ->relatedTo($registration->getClientId())
                ->permittedFor($registration->getPlatform()->getAudience())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
                ->getToken($this->signer, $registration->getToolKeyChain()->getPrivateKey())
                ->__toString();

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate credentials: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
