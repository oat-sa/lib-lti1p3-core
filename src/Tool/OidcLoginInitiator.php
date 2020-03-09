<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tool;

use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequest;
use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequestParameters;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequestParameters;
use OAT\Library\Lti1p3Core\Security\Oidc\StateGenerator;
use OAT\Library\Lti1p3Core\Security\Oidc\StateGeneratorInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request
 */
class OidcLoginInitiator
{
    /** @var DeploymentRepositoryInterface */
    private $deploymentRepository;

    /** @var NonceRepositoryInterface */
    private $nonceRepository;

    /** @var NonceGeneratorInterface */
    private $nonceGenerator;

    /** @var StateGeneratorInterface */
    private $stateGenerator;

    public function __construct(
        DeploymentRepositoryInterface $deploymentRepository,
        NonceRepositoryInterface $nonceRepository,
        NonceGeneratorInterface $nonceGenerator = null,
        StateGeneratorInterface $stateGenerator = null
    ) {
        $this->deploymentRepository = $deploymentRepository;
        $this->nonceRepository = $nonceRepository;
        $this->nonceGenerator = $nonceGenerator ?? new NonceGenerator();
        $this->stateGenerator = $stateGenerator ?? new StateGenerator();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function initiate(LoginInitiationRequestParameters $lLoginInitiationParameters): AuthenticationRequest
    {
        try {
            $deployment = $this->deploymentRepository->findByIssuer(
                $lLoginInitiationParameters->getIssuer(),
                $lLoginInitiationParameters->getClientId()
            );

            if (null === $deployment) {
                throw new LtiException(
                    sprintf('Deployment not found for issuer %s', $lLoginInitiationParameters->getIssuer())
                );
            }

            $authenticationRequestParameters = new AuthenticationRequestParameters(
                $lLoginInitiationParameters->getTargetLinkUri(),
                $deployment->getClientId(),
                $lLoginInitiationParameters->getLoginHint(),
                $this->generateNonce()->getValue(),
                $this->stateGenerator->generate($deployment, $lLoginInitiationParameters)->__toString(),
                $lLoginInitiationParameters->getLtiMessageHint()
            );

            return new AuthenticationRequest(
                $deployment->getPlatform()->getOidcAuthenticationUrl(),
                $authenticationRequestParameters
            );
        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('OIDC Login Initiation error: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function generateNonce(): NonceInterface
    {
        $nonce = $this->nonceGenerator->generate();

        $this->nonceRepository->save($nonce);

        return $nonce;
    }
}
