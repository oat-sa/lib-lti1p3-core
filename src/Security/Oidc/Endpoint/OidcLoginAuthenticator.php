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

namespace OAT\Library\Lti1p3Core\Security\Oidc\Endpoint;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use OAT\Library\Lti1p3Core\Security\Oidc\Request\OidcAuthenticationRequest;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-3-authentication-response
 */
class OidcLoginAuthenticator
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var UserAuthenticatorInterface */
    private $authenticator;

    /** @var LtiLaunchRequestBuilder */
    private $requestBuilder;

    /** @var Signer */
    private $signer;

    /** @var Parser */
    private $parser;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        UserAuthenticatorInterface $authenticator,
        LtiLaunchRequestBuilder $requestBuilder = null,
        Signer $signer = null
    ) {
        $this->repository = $repository;
        $this->authenticator = $authenticator;
        $this->requestBuilder = $requestBuilder ?? new LtiLaunchRequestBuilder();
        $this->signer = $signer ?? new Sha256();
        $this->parser = new Parser(new AssociativeDecoder());
    }

    /**
     * @throws LtiException
     */
    public function authenticate(ServerRequestInterface $request): LtiLaunchRequest
    {
        try {
            /** @var OidcAuthenticationRequest $oidcRequest */
            $oidcRequest = OidcAuthenticationRequest::fromServerRequest($request);

            $originalMessage = new LtiMessage($this->parser->parse($oidcRequest->getLtiMessageHint()));

            $registration = $this->repository->find(
                $originalMessage->getMandatoryClaim(MessageInterface::CLAIM_REGISTRATION_ID)
            );

            if (null === $registration) {
                throw new LtiException('Invalid message hint registration id claim');
            }

            if (!$originalMessage->getToken()->verify($this->signer, $registration->getPlatformKeyChain()->getPublicKey())) {
               throw new LtiException('Invalid message hint signature');
            }

            if ($originalMessage->getToken()->isExpired()) {
                throw new LtiException('Message hint expired');
            }

            $originalResourceLink = new ResourceLink(
                $originalMessage->getResourceLink()->getId(),
                $originalMessage->getTargetLinkUri(),
                $originalMessage->getResourceLink()->getTitle(),
                $originalMessage->getResourceLink()->getDescription()
            );

            $authenticationResult = $this->authenticator->authenticate($oidcRequest->getLoginHint());

            if (!$authenticationResult->isSuccess()) {
                throw new LtiException('User authentication failure');
            }

            if (!$authenticationResult->isAnonymous()) {
                return $this->requestBuilder
                    ->copyFromMessage($originalMessage)
                    ->buildUserResourceLinkLtiLaunchRequest(
                        $originalResourceLink,
                        $registration,
                        $authenticationResult->getUserIdentity(),
                        $originalMessage->getDeploymentId(),
                        $originalMessage->getRoles(),
                        [],
                        $oidcRequest->getState()
                    );
            }

            return $this->requestBuilder
                ->copyFromMessage($originalMessage)
                ->buildResourceLinkLtiLaunchRequest(
                    $originalResourceLink,
                    $registration,
                    $originalMessage->getDeploymentId(),
                    $originalMessage->getRoles(),
                    [],
                    $oidcRequest->getState()
                );

        } catch (LtiException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('OIDC login authentication failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
