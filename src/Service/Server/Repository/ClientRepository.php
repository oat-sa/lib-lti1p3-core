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

namespace OAT\Library\Lti1p3Core\Service\Server\Repository;

use Carbon\Carbon;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Service\Server\Entity\Client;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant
 */
class ClientRepository implements ClientRepositoryInterface
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var JwksFetcherInterface */
    private $fetcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var Signer */
    private $signer;

    /** @var Parser */
    private $parser;

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        JwksFetcherInterface $jwksFetcher = null,
        LoggerInterface $logger = null,
        Signer $signer = null
    ) {
        $this->repository = $registrationRepository;
        $this->fetcher = $jwksFetcher ?? new JwksFetcher();
        $this->logger = $logger ?? new NullLogger();
        $this->signer = $signer ?? new Sha256();
        $this->parser = new Parser();
    }

    public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        $registration = $this->repository->findByClientId($clientIdentifier);

        return $registration ? new Client($registration) : null;
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        if ($grantType !== ClientAssertionCredentialsGrant::GRANT_IDENTIFIER) {
            $this->logger->error('Unhandled grant type ' . $grantType);

            return false;
        }

        try {
            $token = $this->parser->parse($clientSecret);
        } catch (Throwable $exception) {
            $this->logger->error('Cannot parse the client_assertion JWT: ' . $exception->getMessage());

            return false;
        }

        if ($token->isExpired(Carbon::now())) {
            $this->logger->error('The client_assertion JWT is expired');

            return false;
        }

        $registration = $this->repository->findByClientId($clientIdentifier);

        if (null === $registration) {
            $this->logger->error('Cannot find registration for client_id ' . $clientIdentifier);

            return false;
        }

        if ($token->getClaim('aud') !== $registration->getPlatform()->getOAuth2AccessTokenUrl()) {
            $this->logger->error('Invalid audience: ' . $token->getClaim('aud'));

            return false;
        }

        try {
            if (null === $registration->getToolKeyChain()) {
                $key = $this->fetcher->fetchKey($registration->getToolJwksUrl(), $token->getHeader(MessageInterface::HEADER_KID));
            } else {
                $key = $registration->getToolKeyChain()->getPublicKey();
            }
        } catch (Throwable $exception) {
            $this->logger->error('Cannot find signing public key: ' . $exception->getMessage());

            return false;
        }

        if (!$token->verify($this->signer, $key)) {
            $this->logger->error('Invalid JWT signature');

            return false;
        }

        return true;
    }
}
