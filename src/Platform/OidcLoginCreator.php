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

namespace OAT\Library\Lti1p3Core\Platform;

use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequest;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequestParameters;
use OAT\Library\Lti1p3Core\Security\Oidc\LtiMessageHintGenerator;
use OAT\Library\Lti1p3Core\Security\Oidc\LtiMessageHintGeneratorInterface;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
 */
class OidcLoginCreator
{
    /** @var DeploymentRepositoryInterface */
    private $deploymentRepository;

    /** @var LtiMessageHintGeneratorInterface */
    private $ltiMessageHintGenerator;

    public function __construct(
        DeploymentRepositoryInterface $deploymentRepository,
        LtiMessageHintGeneratorInterface $ltiMessageHintGenerator = null
    ) {
        $this->deploymentRepository = $deploymentRepository;
        $this->ltiMessageHintGenerator = $ltiMessageHintGenerator ?? new LtiMessageHintGenerator();
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function create(
        string $issuer,
        string $loginHint,
        string $targetLinkUri,
        string $clientId = null): LoginInitiationRequest {
        try {
            $deployment = $this->deploymentRepository->findByIssuer($issuer, $clientId);

            if (null === $deployment) {
                throw new LtiException(sprintf('Deployment not found for issuer %s', $issuer));
            }

            $loginInitiationRequestParameters = new LoginInitiationRequestParameters(
                $issuer,
                $loginHint,
                $targetLinkUri,
                $this->ltiMessageHintGenerator->generate($deployment)->__toString(),
                $deployment->getId(),
                $deployment->getClientId()
            );

            return new LoginInitiationRequest(
                $deployment->getTool()->getOidcLoginInitiationUrl(),
                $loginInitiationRequestParameters
            );
        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('OIDC Login Creation error: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
