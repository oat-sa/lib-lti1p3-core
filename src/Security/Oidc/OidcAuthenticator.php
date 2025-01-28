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

namespace OAT\Library\Lti1p3Core\Security\Oidc;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiBadRequestException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-3-authentication-response
 */
class OidcAuthenticator
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var UserAuthenticatorInterface */
    private $authenticator;

    /** @var MessagePayloadBuilderInterface */
    private $builder;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ParserInterface */
    private $parser;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        UserAuthenticatorInterface $authenticator,
        ?MessagePayloadBuilderInterface $builder = null,
        ?ValidatorInterface $validator = null,
        ?ParserInterface $parser = null
    ) {
        $this->repository = $repository;
        $this->authenticator = $authenticator;
        $this->builder = $builder ?? new MessagePayloadBuilder();
        $this->validator = $validator ?? new Validator();
        $this->parser = $parser ?? new Parser();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function authenticate(ServerRequestInterface $request): LtiMessageInterface
    {
        try {
            $oidcRequest = LtiMessage::fromServerRequest($request);

            if (!$oidcRequest->getParameters()->has('lti_message_hint')) {
                throw new LtiBadRequestException('Missing LTI message hint in request');
            }

            $originalToken = $this->parser->parse($oidcRequest->getParameters()->getMandatory('lti_message_hint'));

            $registration = $this->repository->find(
                $originalToken->getClaims()->getMandatory(LtiMessagePayloadInterface::CLAIM_REGISTRATION_ID)
            );

            if (null === $registration) {
                throw new LtiBadRequestException('Invalid message hint registration id claim');
            }

            if (!$this->validator->validate($originalToken, $registration->getPlatformKeyChain()->getPublicKey())) {
                throw new LtiBadRequestException('Invalid message hint');
            }

            $redirectUri = $this->createToolRedirectUrl($registration, $oidcRequest);

            $authenticationResult = $this->authenticator->authenticate(
                $registration,
                $oidcRequest->getParameters()->getMandatory('login_hint')
            );

            if (!$authenticationResult->isSuccess()) {
                throw new LtiException('User authentication failure');
            }

            $this->builder
                ->withClaims($this->sanitizeClaims($originalToken->getClaims()->all()))
                ->withClaim(LtiMessagePayloadInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_AUD, $registration->getClientId());

            // If the original request contained a nonce, add it to the payload (fixes #154)
            $nonce = $oidcRequest->getParameters()->get('nonce');
            if ($nonce) {
                $this->builder->withClaim(LtiMessagePayloadInterface::CLAIM_NONCE, $nonce);
            }

            if (!$authenticationResult->isAnonymous()) {
                foreach ($authenticationResult->getUserIdentity()->normalize() as $claimName => $claimValue) {
                    $this->builder->withClaim($claimName, $claimValue);
                }
            }

            $payload = $this->builder->buildMessagePayload($registration->getPlatformKeyChain());

            return new LtiMessage(
                $redirectUri,
                [
                    'id_token' => $payload->getToken()->toString(),
                    'state' => $oidcRequest->getParameters()->getMandatory('state')
                ]
            );
        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('OIDC authentication failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function sanitizeClaims(array $claims): array
    {
        foreach (LtiMessagePayloadInterface::RESERVED_USER_CLAIMS as $reservedClaim) {
            unset($claims[$reservedClaim]);
        }

        return $claims;
    }

    private function createToolRedirectUrl(
        RegistrationInterface $registration,
        LtiMessageInterface $oidcRequest
    ): string {
        $uri = $oidcRequest->getParameters()->getMandatory('redirect_uri');
        $host = parse_url($uri, PHP_URL_HOST);
        /**
         * redirect_uri must match one of the values associated with the tool's registration
         * however, the library does not support multiple redirect_uris per registration,
         * and relies on the launch URL to represent the tool's redirect_uri
         *
         * in order to support multiple redirect_uris, while still keeping the authentication
         * relatively safe, the redirect_uri's host is compared against the pre-registered launch URL's host
         */
        if (!$host || $host !== parse_url($registration->getTool()->getLaunchUrl() ?? '', PHP_URL_HOST)) {
            throw new LtiBadRequestException('Invalid redirect_uri');
        }

        return $uri;
    }
}
