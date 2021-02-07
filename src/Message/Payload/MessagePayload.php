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

namespace OAT\Library\Lti1p3Core\Message\Payload;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\MessagePayloadClaimInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#json-web-token-0
 */
class MessagePayload implements MessagePayloadInterface
{
    /** @var TokenInterface */
    protected $token;

    public function __construct(TokenInterface $token)
    {
        $this->token = $token;
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getMandatoryClaim(string $claim)
    {
        if (!$this->hasClaim($claim)) {
            throw new LtiException(sprintf('Cannot get mandatory %s claim', $claim));
        }

        return $this->getClaim($claim);
    }

    public function getClaim(string $claim, $default = null)
    {
        if (is_a($claim, MessagePayloadClaimInterface::class, true)) {
            /** @var MessagePayloadClaimInterface $claim */
            return $this->token->getClaims()->has($claim::getClaimName())
                ? $claim::denormalize($this->token->getClaims()->get($claim::getClaimName()))
                : $default;
        }

        return $this->token->getClaims()->get($claim, $default);
    }

    public function hasClaim(string $claim): bool
    {
        if (is_a($claim, MessagePayloadClaimInterface::class, true)) {
            /**  @var MessagePayloadClaimInterface $claim */
            return $this->token->getClaims()->has($claim::getClaimName());
        }

        return $this->token->getClaims()->has($claim);
    }
}
