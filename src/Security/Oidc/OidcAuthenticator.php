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

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Token\Builder\MessageTokenBuilder;
use OAT\Library\Lti1p3Core\Message\Token\Builder\MessageTokenBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Token\LtiMessageToken;
use OAT\Library\Lti1p3Core\Message\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
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

    /** @var MessageTokenBuilderInterface */
    private $builder;

    /** @var Signer */
    private $signer;

    /** @var Parser */
    private $parser;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        UserAuthenticatorInterface $authenticator,
        MessageTokenBuilderInterface $builder = null,
        Signer $signer = null
    ) {
        $this->repository = $repository;
        $this->authenticator = $authenticator;
        $this->builder = $builder ?? new MessageTokenBuilder();
        $this->signer = $signer ?? new Sha256();
        $this->parser = new Parser(new AssociativeDecoder());
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function authenticate(ServerRequestInterface $request): LtiMessageInterface
    {
        try {
            $oidcRequest = LtiMessage::fromServerRequest($request);

            $originalMessageToken = new LtiMessageToken(
                $this->parser->parse($oidcRequest->getParameter('lti_message_hint'))
            );

            if ($originalMessageToken->getToken()->isExpired()) {
                throw new LtiException('Message hint expired');
            }

            $registration = $this->repository->find(
                $originalMessageToken->getMandatoryClaim(LtiMessageTokenInterface::CLAIM_REGISTRATION_ID)
            );

            if (null === $registration) {
                throw new LtiException('Invalid message hint registration id claim');
            }

            if (!$originalMessageToken->getToken()->verify($this->signer, $registration->getPlatformKeyChain()->getPublicKey())) {
               throw new LtiException('Invalid message hint signature');
            }

            $authenticationResult = $this->authenticator->authenticate($oidcRequest->getMandatoryParameter('login_hint'));

            if (!$authenticationResult->isSuccess()) {
                throw new LtiException('User authentication failure');
            }

            $this->builder->withMessageTokenClaims($originalMessageToken);

            if (!$authenticationResult->isAnonymous()) {
                foreach ($authenticationResult->getUserIdentity()->normalize() as $claimName => $claimValue) {
                    $this->builder->withClaim($claimName, $claimValue);
                }
            }

            $messageToken = $this->builder->buildMessageToken($registration->getPlatformKeyChain());

            return new LtiMessage(
                $originalMessageToken->getMandatoryClaim(LtiMessageTokenInterface::CLAIM_LTI_TARGET_LINK_URI),
                [
                    'id_token' => $messageToken->getToken()->__toString(),
                    'state' => $oidcRequest->getMandatoryParameter('state')
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
}
