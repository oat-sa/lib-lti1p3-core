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

namespace OAT\Library\Lti1p3Core\Service\Server\Factory;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\JwtClientCredentialsGrant;
use OAT\Library\Lti1p3Core\Service\Server\ResponseType\ScopeBearerResponseType;

class OAuth2AuthorizationServerFactory
{
    /** @var AccessTokenRepositoryInterface */
    private $accessTokenRepository;

    /** @var ClientRepositoryInterface */
    private $clientRepository;

    /** @var ScopeRepositoryInterface */
    private $scopeRepository;

    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var CryptKey */
    private $privateKey;

    /** @var string */
    private $encryptionKey;

    public function __construct(
        AccessTokenRepositoryInterface $accessTokenRepository,
        ClientRepositoryInterface $clientRepository,
        ScopeRepositoryInterface $scopeRepository,
        RegistrationRepositoryInterface $registrationRepository,
        CryptKey $privateKey,
        string $encryptionKey
    ) {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->clientRepository = $clientRepository;
        $this->scopeRepository = $scopeRepository;
        $this->registrationRepository = $registrationRepository;
        $this->privateKey = $privateKey;
        $this->encryptionKey = $encryptionKey;
    }

    public function create(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->privateKey,
            $this->encryptionKey,
            new ScopeBearerResponseType()
        );

        $server->enableGrantType(new JwtClientCredentialsGrant($this->registrationRepository));

        return $server;
    }
}
