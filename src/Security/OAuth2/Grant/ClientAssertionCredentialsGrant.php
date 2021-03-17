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

namespace OAT\Library\Lti1p3Core\Security\OAuth2\Grant;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant
 */
class ClientAssertionCredentialsGrant extends ClientCredentialsGrant
{
    public const GRANT_TYPE = 'client_credentials';
    public const GRANT_IDENTIFIER = 'client_assertion_credentials';
    public const CLIENT_ASSERTION_TYPE = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

    /** @var ParserInterface */
    private $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function getIdentifier(): string
    {
        return static::GRANT_IDENTIFIER;
    }

    public function canRespondToAccessTokenRequest(ServerRequestInterface $request): bool
    {
        $body = (array)$request->getParsedBody();

        return
            array_key_exists('grant_type', $body)
            && $body['grant_type'] === static::GRANT_TYPE
            && array_key_exists('client_assertion', $body)
            && array_key_exists('client_assertion_type', $body)
            && $body['client_assertion_type'] === static::CLIENT_ASSERTION_TYPE;
    }

    /**
     * @throws OAuthServerException
     */
    protected function getClientCredentials(ServerRequestInterface $request): array
    {
        $clientAssertion = $this->getRequestParameter('client_assertion', $request);

        try {
            $token = $this->parser->parse($clientAssertion);

            return [
                $token->getClaims()->getMandatory('sub'),
                $clientAssertion
            ];
        } catch (Throwable $exception) {
            throw OAuthServerException::invalidCredentials();
        }
    }
}
