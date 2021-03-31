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

namespace OAT\Library\Lti1p3Core\Message\Launch\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;

abstract class AbstractLaunchBuilder
{
    /** @var MessagePayloadBuilderInterface */
    protected $builder;

    public function __construct(?MessagePayloadBuilderInterface $builder = null)
    {
        $this->builder = $builder ?? new MessagePayloadBuilder();
    }

    protected function applyOptionalClaims(array $optionalClaims): self
    {
        $this->builder->withClaims($optionalClaims);

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    protected function resolveDeploymentId(RegistrationInterface $registration, ?string $deploymentId = null): string
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
}
