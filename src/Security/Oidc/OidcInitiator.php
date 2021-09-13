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
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request
 */
class OidcInitiator
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var NonceGeneratorInterface */
    private $generator;

    /** @var MessagePayloadBuilderInterface */
    private $builder;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        ?NonceGeneratorInterface $generator = null,
        ?MessagePayloadBuilderInterface $builder = null
    ) {
        $this->repository = $repository;
        $this->generator = $generator ?? new NonceGenerator();
        $this->builder = $builder ?? new MessagePayloadBuilder();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function initiate(ServerRequestInterface $request): LtiMessageInterface
    {
        try {
            $oidcRequest = LtiMessage::fromServerRequest($request);

            $registration = $this->repository->findByPlatformIssuer(
                $oidcRequest->getParameters()->getMandatory('iss'),
                $oidcRequest->getParameters()->get('client_id')
            );

            if (null === $registration) {
                throw new LtiException('Cannot find registration for OIDC request');
            }

            $toolKeyChain = $registration->getToolKeyChain();

            if (null === $toolKeyChain) {
                throw new LtiException(
                    sprintf('Registration %s does not have a configured tool key chain', $registration->getIdentifier())
                );
            }

            $deploymentId = $oidcRequest->getParameters()->get('lti_deployment_id');

            if (null !== $deploymentId) {
                if (!$registration->hasDeploymentId($deploymentId)) {
                    throw new LtiException('Cannot find deployment for OIDC request');
                }
            }

            $nonce = $this->generator->generate();

            $this->builder
                ->withClaim(LtiMessagePayloadInterface::CLAIM_SUB, $registration->getIdentifier())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_ISS, $registration->getTool()->getAudience())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_AUD, $registration->getPlatform()->getAudience())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_NONCE, $nonce->getValue())
                ->withClaim(LtiMessagePayloadInterface::CLAIM_PARAMETERS, $oidcRequest->getParameters());

            $statePayload = $this->builder->buildMessagePayload($toolKeyChain);

            return new LtiMessage(
                $registration->getPlatform()->getOidcAuthenticationUrl(),
                [
                    'redirect_uri' => $oidcRequest->getParameters()->getMandatory('target_link_uri'),
                    'client_id' => $registration->getClientId(),
                    'login_hint' => $oidcRequest->getParameters()->getMandatory('login_hint'),
                    'nonce' => $nonce->getValue(),
                    'state' => $statePayload->getToken()->toString(),
                    'lti_message_hint' => $oidcRequest->getParameters()->get('lti_message_hint'),
                    'scope' => 'openid',
                    'response_type' => 'id_token',
                    'response_mode' => 'form_post',
                    'prompt' => 'none'
                ]
            );

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('OIDC initiation failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
