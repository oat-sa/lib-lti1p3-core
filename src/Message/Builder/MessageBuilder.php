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

namespace OAT\Library\Lti1p3Core\Message\Builder;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Claim\MessageClaimInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Message;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class MessageBuilder
{
    /** @var NonceGeneratorInterface */
    private $generator;

    /** @var Builder */
    private $builder;

    /** @var Signer */
    private $signer;

    public function __construct(NonceGeneratorInterface $generator = null, Builder $builder = null, Signer $signer = null)
    {
        $this->generator = $generator ?? new NonceGenerator();
        $this->builder = $builder ?? new Builder();
        $this->signer = $signer ?? new Sha256();
    }

    public function withClaim($claim, $claimValue = null): self
    {
        if (is_a($claim, MessageClaimInterface::class, true)) {
            /**  @var MessageClaimInterface $claim */
            $this->builder->withClaim($claim::getClaimName(), $claim->normalize());
        } else {
            $this->builder->withClaim((string)$claim, $claimValue);
        }

        return $this;
    }

    /**
     * @throws LtiException
     */
    public function getMessage(KeyChainInterface $keyChain): MessageInterface
    {
        return new Message($this->getMessageToken($keyChain));
    }

    /**
     * @throws LtiException
     */
    public function getLtiMessage(KeyChainInterface $keyChain): LtiMessageInterface
    {
        $this->builder->withClaim(LtiMessageInterface::CLAIM_LTI_VERSION, LtiMessageInterface::LTI_VERSION);

        return new LtiMessage($this->getMessageToken($keyChain));
    }

    /**
     * @throws LtiException
     */
    protected function getMessageToken(KeyChainInterface $keyChain): Token
    {
        try {
            $now = Carbon::now();

            $this->builder
                ->withHeader(MessageInterface::HEADER_KID, $keyChain->getIdentifier())
                ->withClaim(MessageInterface::CLAIM_NONCE, $this->generator->generate()->getValue())
                ->identifiedBy(Uuid::uuid4()->toString())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds(MessageInterface::TTL)->getTimestamp());

            return $this->builder->getToken($this->signer, $keyChain->getPrivateKey());
        } catch (Throwable $exception) {
            throw new LtiException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
