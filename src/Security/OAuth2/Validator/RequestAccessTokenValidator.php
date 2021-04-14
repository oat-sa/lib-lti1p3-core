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

namespace OAT\Library\Lti1p3Core\Security\OAuth2\Validator;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResult;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResultInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class RequestAccessTokenValidator implements RequestAccessTokenValidatorInterface
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var LoggerInterface */
    private $logger;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ParserInterface */
    private $parser;

    /** @var string[] */
    private $successes = [];

    public function __construct(
        RegistrationRepositoryInterface $repository,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $validator = null,
        ?ParserInterface $parser = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
        $this->validator = $validator ?? new Validator();
        $this->parser = $parser ?? new Parser();
    }

    public function validate(
        ServerRequestInterface $request,
        array $allowedScopes = []
    ): RequestAccessTokenValidationResultInterface {
        $this->reset();

        try {
            if (!$request->hasHeader('Authorization')) {
                throw new LtiException('Missing Authorization header');
            }

            $token = $this->parser->parse(
                substr($request->getHeaderLine('Authorization'), strlen('Bearer '))
            );

            $registration = null;

            $audiences = $token->getClaims()->getMandatory('aud');
            $audiences = is_array($audiences) ? $audiences : [$audiences];

            foreach ($audiences as $audience) {
                $registration = $this->repository->findByClientId($audience);

                if (null !== $registration) {
                    $this->addSuccess('Registration found for client_id: ' . $audience);
                    break;
                }
            }

            if (null === $registration) {
                throw new LtiException(
                    sprintf('No registration found with client_id for audience(s) %s', implode(', ', $audiences))
                );
            }

            if (null === $registration->getPlatformKeyChain()) {
                throw new LtiException('Missing platform key chain for registration: ' . $registration->getIdentifier());
            }

            $this->addSuccess('Platform key chain found for registration: ' . $registration->getIdentifier());

            if (!$this->validator->validate($token, $registration->getPlatformKeyChain()->getPublicKey())) {
                throw new LtiException('JWT access token is invalid');
            }

            $this->addSuccess('JWT access token is valid');

            if (empty(array_intersect($token->getClaims()->get('scopes', []), $allowedScopes))) {
                throw new LtiException('JWT access token scopes are invalid');
            }

            $this->addSuccess('JWT access token scopes are valid');

            return new RequestAccessTokenValidationResult($registration, $token, $this->successes);

        } catch (Throwable $exception) {
            $this->logger->error('Access token validation error: ' . $exception->getMessage());

            return new RequestAccessTokenValidationResult(null, null, $this->successes, $exception->getMessage());
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
