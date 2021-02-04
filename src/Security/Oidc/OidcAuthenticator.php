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

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Jwt\ConfigurationFactory;
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

    /** @var ConfigurationFactory */
    private $factory;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        UserAuthenticatorInterface $authenticator,
        MessagePayloadBuilderInterface $builder = null,
        ConfigurationFactory $factory = null
    ) {
        $this->repository = $repository;
        $this->authenticator = $authenticator;
        $this->builder = $builder ?? new MessagePayloadBuilder();
        $this->factory = $factory ?? new ConfigurationFactory();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function authenticate(ServerRequestInterface $request): LtiMessageInterface
    {
        try {
            $oidcRequest = LtiMessage::fromServerRequest($request);

            $originalToken = $this->factory->create()->parser()->parse(
                $oidcRequest->getParameters()->get('lti_message_hint')
            );

            $originalPayload = new LtiMessagePayload($originalToken);

            $registration = $this->repository->find(
                $originalPayload->getMandatoryClaim(LtiMessagePayloadInterface::CLAIM_REGISTRATION_ID)
            );

            if (null === $registration) {
                throw new LtiException('Invalid message hint registration id claim');
            }

            $config = $this->factory->create(
                $registration->getPlatformKeyChain()->getPrivateKey(),
                $registration->getPlatformKeyChain()->getPublicKey()
            );

            if (!$config->validator()->validate($originalToken, ...$config->validationConstraints())) {
                throw new LtiException('Invalid message hint');
            }

            $authenticationResult = $this->authenticator->authenticate(
                $oidcRequest->getParameters()->getMandatory('login_hint')
            );

            if (!$authenticationResult->isSuccess()) {
                throw new LtiException('User authentication failure');
            }

            $this->builder
                ->withMessagePayloadClaims($originalPayload)
                ->withClaim(LtiMessagePayloadInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_AUD, $registration->getClientId());

            if (!$authenticationResult->isAnonymous()) {
                foreach ($authenticationResult->getUserIdentity()->normalize() as $claimName => $claimValue) {
                    $this->builder->withClaim($claimName, $claimValue);
                }
            }

            $payload = $this->builder->buildMessagePayload($registration->getPlatformKeyChain());

            return new LtiMessage(
                $originalPayload->getMandatoryClaim(LtiMessagePayloadInterface::CLAIM_LTI_TARGET_LINK_URI),
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
}
