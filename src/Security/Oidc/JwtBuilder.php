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
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Launch\Message\LoginMessage;
use Ramsey\Uuid\Uuid;

class JwtBuilder
{
    public const DEFAULT_TTL = 500;

    /** @var Signer */
    private $signer;

    /** @var int */
    private $ttl;

    public function __construct(Signer $signer, int $ttl = null)
    {
        $this->signer = $signer;
        $this->ttl = $ttl ?? static::DEFAULT_TTL;
    }

    public function generate(DeploymentInterface $deployment, LoginMessage $loginMessage): string
    {
        $timestamp = Carbon::now()->getTimestamp();

        return (new Builder())
            ->identifiedBy(Uuid::uuid4())
            ->issuedAt($timestamp)
            ->expiresAt($timestamp + $this->ttl)
            ->issuedBy($deployment->getTool()->getName())
            ->relatedTo($deployment->getClientId())
            ->permittedFor($deployment->getPlatform()->getOAuth2AccessTokenUrl())
            ->withClaim(
                'params',
                array_merge(
                    ['utf8' => true],
                    $loginMessage->export()
                )
            )
            ->getToken($this->signer, $deployment->getToolKeyPair()->getPrivateKey())
            ->__toString();
    }
}
