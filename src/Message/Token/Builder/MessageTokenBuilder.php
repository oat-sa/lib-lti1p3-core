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

namespace OAT\Library\Lti1p3Core\Message\Token\Builder;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Token\Claim\MessageTokenClaimInterface;
use OAT\Library\Lti1p3Core\Message\Token\MessageToken;
use OAT\Library\Lti1p3Core\Message\Token\MessageTokenInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class MessageTokenBuilder implements MessageTokenBuilderInterface
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

    public function withClaim($claim, $claimValue = null): MessageTokenBuilderInterface
    {
        if (is_a($claim, MessageTokenClaimInterface::class, true)) {
            /**  @var MessageTokenClaimInterface $claim */
            $this->builder->withClaim($claim::getClaimName(), $claim->normalize());
        } else {
            $this->builder->withClaim((string)$claim, $claimValue);
        }

        return $this;
    }

    public function withMessageTokenClaims(MessageTokenInterface $messageToken): MessageTokenBuilderInterface
    {
        /** @var Claim $claim */
        foreach ($messageToken->getToken()->getClaims() as $claim) {
            $this->builder->withClaim($claim->getName(), $claim->getValue());
        }

        return $this;
    }

    /**
     * @throws LtiException
     */
    public function buildMessageToken(KeyChainInterface $keyChain): MessageTokenInterface
    {
        return new MessageToken($this->getToken($keyChain));
    }

    /**
     * @throws LtiException
     */
    protected function getToken(KeyChainInterface $keyChain): Token
    {
        try {
            $now = Carbon::now();

            $this->builder
                ->withHeader(MessageTokenInterface::HEADER_KID, $keyChain->getIdentifier())
                ->withClaim(MessageTokenInterface::CLAIM_NONCE, $this->generator->generate()->getValue())
                ->identifiedBy(Uuid::uuid4()->toString())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds(MessageTokenInterface::TTL)->getTimestamp());

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
