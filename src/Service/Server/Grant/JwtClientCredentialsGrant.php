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

namespace OAT\Library\Lti1p3Core\Service\Server\Grant;

use Carbon\Carbon;
use DateInterval;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtClientCredentialsGrant extends AbstractGrant
{
    /** @var DateInterval */
    protected $refreshTokenTTL;

    /** @var ClientRepositoryInterface */
    protected $clientRepository;

    /** @var AccessTokenRepositoryInterface */
    protected $accessTokenRepository;

    /** @var ScopeRepositoryInterface */
    protected $scopeRepository;

    /** @var DeploymentRepositoryInterface */
    private $deploymentRepository;

    public function __construct(DeploymentRepositoryInterface $deploymentRepository)
    {
        $this->deploymentRepository = $deploymentRepository;
    }

    public function getIdentifier(): string
    {
        return 'client_credentials';
    }

    public function canRespondToAccessTokenRequest(ServerRequestInterface $request): bool
    {
        $body = (array)$request->getParsedBody();

        return
            array_key_exists('grant_type', $body)
            && $body['grant_type'] === $this->getIdentifier()
            && array_key_exists('client_assertion', $body)
            && array_key_exists('client_assertion_type', $body)
            && $body['client_assertion_type'] === 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $body = (array)$request->getParsedBody();

        // Validate request
        $jws = $this->validateAssertion($request);
        $scopes = $this->validateScopes($body['scope'] ?? null);

        $client = $this->clientRepository->getClientEntity($jws['iss']);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client);

        // Issue and persist access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, null, $finalizedScopes);

        // Send event to emitter
        $this->getEmitter()->emit(new RequestEvent('access_token.issued', $request));

        // Inject access token into response type
        $responseType->setAccessToken($accessToken);

        return $responseType;
    }

    /**
     * @throws OAuthServerException
     */
    protected function validateAssertion(ServerRequestInterface $request): array
    {
        $body = (array)$request->getParsedBody();

        $assertion = $body['client_assertion'];

        try {
            $token = (new Parser())->parse($assertion);
        } catch (InvalidArgumentException $exception) {
            throw OAuthServerException::invalidRequest('client_assertion', 'Invalid JWT token provided');
        }

        // looking for deployment
        $deployment = $this->deploymentRepository->findByIssuer($token->getClaim('iss'), $token->getClaim('sub'));

        if (null === $deployment) {
            throw OAuthServerException::invalidRequest('client_assertion', 'Deployment not found');
        }

        if ($token->isExpired(Carbon::now())) {
            throw OAuthServerException::invalidRequest('client_assertion', 'Provided JWT is expired');
        }

        $toolKeyChain = $deployment->getToolKeyChain();

        if (null === $toolKeyChain) {
            throw OAuthServerException::invalidRequest('client_assertion', 'Tool Key Chain not found');
        }

        if (!$token->verify(new Sha256(), $toolKeyChain->getPublicKey())) {
            throw OAuthServerException::invalidRequest('client_assertion', 'Provided JWT signature is not valid');
        }

        return $token->getClaims();
    }
}
