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

namespace OAT\Library\Lti1p3Core\Security\Oidc;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use Ramsey\Uuid\Uuid;
use Throwable;

class StateGenerator implements StateGeneratorInterface
{
    /** @var int */
    private $ttl;

    /** @var Signer */
    private $signer;

    public function __construct(int $ttl = null, Signer $signer = null)
    {
        $this->ttl = $ttl ?? static::DEFAULT_TTL;
        $this->signer = $signer ?? new Sha256();
    }

    /**
     * @throws LtiException
     */
    public function generate(DeploymentInterface $deployment, LoginInitiationRequestParameters $parameters): string
    {
        try {
            $now = Carbon::now();

            return (new Builder())
                ->identifiedBy(Uuid::uuid4())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds($this->ttl)->getTimestamp())
                ->issuedBy($deployment->getTool()->getName())
                ->relatedTo($deployment->getClientId())
                ->permittedFor($deployment->getPlatform()->getOAuth2AccessTokenUrl())
                ->withClaim('params', [
                    'iss' => $parameters->getIssuer(),
                    'login_hint' => $parameters->getLoginHint(),
                    'target_link_uri' => $parameters->getTargetLinkUri(),
                    'lti_message_hint' => $parameters->getLtiMessageHint(),
                    'lti_deployment_id' => $parameters->getLtiDeploymentId(),
                    'client_id' => $parameters->getClientId(),
                ])
                ->getToken($this->signer, $deployment->getToolKeyPair()->getPrivateKey())
                ->__toString();
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('State generation failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
