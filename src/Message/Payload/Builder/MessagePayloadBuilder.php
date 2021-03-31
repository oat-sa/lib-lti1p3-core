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

namespace OAT\Library\Lti1p3Core\Message\Payload\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\MessagePayloadClaimInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\BuilderInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;
use Throwable;

class MessagePayloadBuilder implements MessagePayloadBuilderInterface
{
    /** @var NonceGeneratorInterface */
    private $generator;

    /** @var BuilderInterface */
    private $builder;

    /** @var CollectionInterface */
    private $claims;

    public function __construct(?NonceGeneratorInterface $generator = null, ?BuilderInterface $builder = null)
    {
        $this->generator = $generator ?? new NonceGenerator();
        $this->builder = $builder ?? new Builder();
        $this->claims = new Collection();
    }

    public function reset(): MessagePayloadBuilderInterface
    {
        $this->claims->replace([]);

        return $this;
    }

    public function withClaims(array $claims): MessagePayloadBuilderInterface
    {
        foreach ($claims as $claimName => $claimValue){
            if (is_a($claimValue, MessagePayloadClaimInterface::class)) {
                $this->withClaim($claimValue);
            } else {
                $this->withClaim((string)$claimName, $claimValue);
            }
        }

        return $this;
    }

    public function withClaim($claim, $claimValue = null): MessagePayloadBuilderInterface
    {
        if (is_a($claim, MessagePayloadClaimInterface::class)) {
            $this->claims->set($claim::getClaimName(), $claim->normalize());
        } else {
            $this->claims->set((string)$claim, $claimValue);
        }

        return $this;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function buildMessagePayload(KeyChainInterface $keyChain): MessagePayloadInterface
    {
        return new MessagePayload($this->getToken($keyChain));
    }

    /**
     * @throws LtiExceptionInterface
     */
    protected function getToken(KeyChainInterface $keyChain): TokenInterface
    {
        try {
            $headers = [
                MessagePayloadInterface::HEADER_KID => $keyChain->getIdentifier()
            ];

            $claims = array_merge(
                $this->claims->all(),
                [
                    MessagePayloadInterface::CLAIM_NONCE => $this->generator->generate()->getValue()
                ]
            );

            return $this->builder->build($headers, $claims, $keyChain->getPrivateKey());

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate message token: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
