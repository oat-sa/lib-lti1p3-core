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

namespace OAT\Library\Lti1p3Core\Launch\Builder;

use Lcobucci\JWT\Claim;
use OAT\Library\Lti1p3Core\Message\Claim\MessageClaimInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use Throwable;

abstract class AbstractLaunchRequestBuilder
{
    /** @var MessageBuilder */
    protected $messageBuilder;

    public function __construct(MessageBuilder $messageBuilder = null)
    {
        $this->messageBuilder = $messageBuilder ?? new MessageBuilder();
    }

    public function copyFromMessage(MessageInterface $message): self
    {
        /** @var Claim $claim */
        foreach ($message->getToken()->getClaims() as $claim) {
            $this->messageBuilder->withClaim($claim->getName(), $claim->getValue());
        }

        return $this;
    }

    protected function applyOptionalClaims(array $optionalClaims): self
    {
        foreach ($optionalClaims as $claimName => $claim) {
            if ($claim instanceof MessageClaimInterface) {
                $this->messageBuilder->withClaim($claim);
            } else {
                $this->messageBuilder->withClaim($claimName, $claim);
            }
        }

        return $this;
    }

    /**
     * @throws LtiException
     */
    protected function resolveDeploymentId(RegistrationInterface $registration, string $deploymentId = null): string
    {
        if (null !== $deploymentId) {
            if (!$registration->hasDeploymentId($deploymentId)) {
                throw new LtiException(sprintf(
                   'Invalid deployment id %s for registration %s',
                   $deploymentId,
                   $registration->getIdentifier()
               ));
            }
        } else {
            $deploymentId = $registration->getDefaultDeploymentId();

            if (null === $deploymentId) {
                throw new LtiException('Mandatory deployment id is missing');
            }
        }

        return $deploymentId;
    }

    /**
     * @throws LtiException
     */
    protected function convertAndThrowException(Throwable $exception): void
    {
        throw new LtiException(
            sprintf('Cannot create LTI launch request: %s', $exception->getMessage()),
            $exception->getCode(),
            $exception
        );
    }
}
