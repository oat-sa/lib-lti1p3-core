<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Security\OAuth2\Factory;

use InvalidArgumentException;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Security\OAuth2\ResponseType\ScopedBearerTokenResponse;

class AuthorizationServerFactory
{
    /** @var ClientRepositoryInterface */
    private $clientRepository;

    /** @var AccessTokenRepositoryInterface */
    private $accessTokenRepository;

    /** @var ScopeRepositoryInterface */
    private $scopeRepository;

    /** @var string */
    private $encryptionKey;

    public function __construct(
        ClientRepositoryInterface $clientRepository,
        AccessTokenRepositoryInterface $accessTokenRepository,
        ScopeRepositoryInterface $scopeRepository,
        string $encryptionKey
    ) {
        $this->clientRepository = $clientRepository;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->scopeRepository = $scopeRepository;
        $this->encryptionKey = $encryptionKey;
    }

    public function create(KeyChainInterface $keyChain): AuthorizationServer
    {
        $privateKey = $keyChain->getPrivateKey();

        if (null === $privateKey) {
            throw new InvalidArgumentException('Missing private key');
        }

        $cryptKey = new CryptKey(
            $privateKey->getContent(),
            $privateKey->getPassphrase(),
            false
        );

        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $cryptKey,
            $this->encryptionKey,
            new ScopedBearerTokenResponse()
        );

        $server->enableGrantType(new ClientAssertionCredentialsGrant());

        return $server;
    }
}
