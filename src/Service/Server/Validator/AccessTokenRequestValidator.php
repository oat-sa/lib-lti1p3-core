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

namespace OAT\Library\Lti1p3Core\Service\Server\Validator;

use Carbon\Carbon;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class AccessTokenRequestValidator
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var LoggerInterface */
    private $logger;

    /** @var Signer */
    private $signer;

    /** @var Parser */
    private $parser;

    /** @var string[] */
    private $successes = [];

    public function __construct(
        RegistrationRepositoryInterface $repository,
        LoggerInterface $logger = null,
        Signer $signer = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
        $this->signer = $signer ?? new Sha256();
        $this->parser = new Parser();
    }

    public function validate(ServerRequestInterface $request, array $allowedScopes = []): AccessTokenRequestValidationResult
    {
        $this->reset();

        try {
            if (!$request->hasHeader('Authorization')) {
                throw new LtiException('Missing Authorization header');
            }

            $token = $this->parser->parse(
                substr($request->getHeaderLine('Authorization'), strlen('Bearer '))
            );

            if ($token->isExpired(Carbon::now())) {
                throw new LtiException('JWT access token is expired');
            }

            $this->addSuccess('JWT access token is not expired');

            $clientId = $token->getClaim('aud');

            $registration = $this->repository->findByClientId($clientId);

            if (null === $registration) {
                throw new LtiException('No registration found for client_id: ' . $clientId);
            }

            $this->addSuccess('Registration found for client_id: ' . $clientId);

            if (null === $registration->getPlatformKeyChain()) {
                throw new LtiException('Missing platform key chain for registration: ' . $registration->getIdentifier());
            }

            $this->addSuccess('Platform key chain found for registration: ' . $registration->getIdentifier());

            if (!$token->verify($this->signer, $registration->getPlatformKeyChain()->getPublicKey())) {
                throw new LtiException('JWT access token signature is invalid');
            }

            $this->addSuccess('JWT access token signature is valid');

            if (empty(array_intersect($token->getClaim('scopes', []), $allowedScopes))) {
                throw new LtiException('JWT access token scopes are invalid');
            }

            $this->addSuccess('JWT access token scopes are valid');

            return new AccessTokenRequestValidationResult($registration, $token, $this->successes);

        } catch (Throwable $exception) {
            $this->logger->error('Access token validation error: ' . $exception->getMessage());

            return new AccessTokenRequestValidationResult(null, null, $this->successes, $exception->getMessage());
        }
    }

    private function addSuccess(string $message): self
    {
        $this->successes[] = $message;

        return $this;
    }

    private function reset(): self
    {
        $this->successes = [];

        return $this;
    }
}
