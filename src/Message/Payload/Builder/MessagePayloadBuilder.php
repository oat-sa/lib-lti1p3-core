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

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\MessagePayloadClaimInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class MessagePayloadBuilder implements MessagePayloadBuilderInterface
{
    /** @var NonceGeneratorInterface */
    private $generator;

    /** @var Builder */
    private $builder;

    /** @var Signer */
    private $signer;

    public function __construct(
        NonceGeneratorInterface $generator = null,
        Builder $builder = null,
        Signer $signer = null
    ) {
        $this->generator = $generator ?? new NonceGenerator();
        $this->builder = $builder ?? new Builder();
        $this->signer = $signer ?? new Sha256();
    }

    public function reset(): MessagePayloadBuilderInterface
    {
        $this->builder = new Builder();

        return $this;
    }

    public function withClaims(array $claims): MessagePayloadBuilderInterface
    {
        foreach ($claims as $claimName => $claimValue){
            if (is_a($claimValue, MessagePayloadClaimInterface::class, true)) {
                $this->withClaim($claimValue);
            } else {
                $this->withClaim((string)$claimName, $claimValue);
            }
        }

        return $this;
    }

    public function withClaim($claim, $claimValue = null): MessagePayloadBuilderInterface
    {
        if (is_a($claim, MessagePayloadClaimInterface::class, true)) {
            /** @var MessagePayloadClaimInterface $claim */
            $this->builder->withClaim($claim::getClaimName(), $claim->normalize());
        } else {
            $this->builder->withClaim((string)$claim, $claimValue);
        }

        return $this;
    }

    public function withMessagePayloadClaims(
        MessagePayloadInterface $payload,
        array $exclusions = []
    ): MessagePayloadBuilderInterface {
        /** @var Claim $claim */
        foreach ($payload->getToken()->getClaims() as $claim) {
            if (!in_array($claim->getName(), $exclusions)) {
                $this->builder->withClaim($claim->getName(), $claim->getValue());
            }
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
    protected function getToken(KeyChainInterface $keyChain): Token
    {
        try {
            $now = Carbon::now();

            $this->builder
                ->withHeader(MessagePayloadInterface::HEADER_KID, $keyChain->getIdentifier())
                ->withClaim(MessagePayloadInterface::CLAIM_NONCE, $this->generator->generate()->getValue())
                ->identifiedBy(Uuid::uuid4()->toString())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp());

            return $this->builder->getToken($this->signer, $keyChain->getPrivateKey());
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate message token: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
