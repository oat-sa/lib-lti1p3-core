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
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\Token\Builder\MessageTokenBuilder;
use OAT\Library\Lti1p3Core\Message\Token\Builder\MessageTokenBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
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

    /** @var MessageTokenBuilderInterface */
    private $builder;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        NonceGeneratorInterface $generator = null,
        MessageTokenBuilderInterface $builder = null
    ) {
        $this->repository = $repository;
        $this->generator = $generator ?? new NonceGenerator();
        $this->builder = $builder ?? new MessageTokenBuilder();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function initiate(ServerRequestInterface $request): LtiMessageInterface
    {
        try {
            $oidcRequest = LtiMessage::fromServerRequest($request);

            $registration = $registration = $this->repository->findByPlatformIssuer(
                $oidcRequest->getMandatoryParameter('iss'),
                $oidcRequest->getParameter('client_id')
            );

            if (null === $registration) {
                throw new LtiException('Cannot find registration for OIDC request');
            }

            $deploymentId = $oidcRequest->getParameter('lti_deployment_id');

            if (null !== $deploymentId) {
                if (!$registration->hasDeploymentId($deploymentId)) {
                    throw new LtiException('Cannot find deployment for OIDC request');
                }
            }

            $nonce = $this->generator->generate();

            $this->builder
                ->withClaim(LtiMessageTokenInterface::CLAIM_SUB, $registration->getIdentifier())
                ->withClaim(LtiMessageTokenInterface::CLAIM_ISS, $registration->getTool()->getAudience())
                ->withClaim(LtiMessageTokenInterface::CLAIM_AUD, $registration->getPlatform()->getAudience())
                ->withClaim(LtiMessageTokenInterface::CLAIM_NONCE, $nonce->getValue())
                ->withClaim(LtiMessageTokenInterface::CLAIM_PARAMETERS, $oidcRequest->getParameters());

            return new LtiMessage(
                $registration->getPlatform()->getOidcAuthenticationUrl(),
                [
                    'redirect_uri' => $oidcRequest->getMandatoryParameter('target_link_uri'),
                    'client_id' => $registration->getClientId(),
                    'login_hint' => $oidcRequest->getMandatoryParameter('login_hint'),
                    'nonce' => $nonce->getValue(),
                    'state' => $this->builder->buildMessageToken($registration->getToolKeyChain())->getToken()->__toString(),
                    'lti_message_hint' => $oidcRequest->getParameter('lti_message_hint'),
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
                sprintf('OIDC login initiation failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
