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
use RuntimeException;
use Throwable;

class LtiMessageHintGenerator implements LtiMessageHintGeneratorInterface
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
    public function generate(DeploymentInterface $deployment): string
    {
        try {
            $now = Carbon::now();

            if (!$deployment->getPlatformKeyPair()) {
                throw new RuntimeException(
                    sprintf('Deployment id %s does not have a platform key pair configured', $deployment->getId())
                );
            }

            return (new Builder())
                ->identifiedBy(Uuid::uuid4())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds($this->ttl)->getTimestamp())
                ->issuedBy($deployment->getPlatform()->getName())
                ->relatedTo($deployment->getClientId())
                ->permittedFor($deployment->getTool()->getOidcLoginInitiationUrl())
                ->withClaim('deployment_id', $deployment->getId())
                ->getToken($this->signer, $deployment->getPlatformKeyPair()->getPrivateKey())
                ->__toString();
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Lti message hint generation failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
