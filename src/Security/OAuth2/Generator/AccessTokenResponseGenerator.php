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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Security\OAuth2\Generator;

use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Factory\AuthorizationServerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenResponseGenerator implements AccessTokenResponseGeneratorInterface
{
    /** @var KeyChainRepositoryInterface */
    private $repository;

    /** @var AuthorizationServerFactory */
    private $factory;

    public function __construct(KeyChainRepositoryInterface $repository, AuthorizationServerFactory $factory)
    {
        $this->repository = $repository;
        $this->factory = $factory;
    }

    /**
     * @throws OAuthServerException
     */
    public function generate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $keyChainIdentifier
    ): ResponseInterface {
        $keyChain = $this->repository->find($keyChainIdentifier);

        if (null === $keyChain) {
            throw new OAuthServerException('Invalid key chain identifier', 11, 'key_chain_not_found', 404);
        }

        return $this->factory
            ->create($keyChain)
            ->respondToAccessTokenRequest($request, $response);
    }
}
