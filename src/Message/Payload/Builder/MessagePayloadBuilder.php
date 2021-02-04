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
use Lcobucci\JWT\Token\Plain;
use OAT\Library\Lti1p3Core\Collection\Collection;
use OAT\Library\Lti1p3Core\Collection\CollectionInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\MessagePayloadClaimInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\ConfigurationFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGenerator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class MessagePayloadBuilder implements MessagePayloadBuilderInterface
{
    /** @var NonceGeneratorInterface */
    private $generator;

    /** @var ConfigurationFactory */
    private $factory;

    /** @var CollectionInterface */
    private $claims;

    public function __construct(NonceGeneratorInterface $generator = null, ConfigurationFactory $factory = null)
    {
        $this->generator = $generator ?? new NonceGenerator();
        $this->factory = $factory ?? new ConfigurationFactory();
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
            $this->claims->set($claim::getClaimName(), $claim->normalize());
        } else {
            $this->claims->set((string)$claim, $claimValue);
        }

        return $this;
    }

    public function withMessagePayloadClaims(MessagePayloadInterface $payload): MessagePayloadBuilderInterface
    {
        return $this->withClaims($payload->getToken()->claims()->all());
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
    protected function getToken(KeyChainInterface $keyChain): Plain
    {
        try {
            $now = Carbon::now();
            $config = $this->factory->create($keyChain->getPrivateKey());
            $builder = $config->builder();

            foreach ($this->claims->all() as $claimName => $claimValue) {
                switch ($claimName) {
                    case LtiMessagePayloadInterface::CLAIM_JTI:
                    case LtiMessagePayloadInterface::CLAIM_EXP:
                    case LtiMessagePayloadInterface::CLAIM_IAT:
                    case LtiMessagePayloadInterface::CLAIM_NBF:
                        break;
                    case LtiMessagePayloadInterface::CLAIM_SUB:
                        $builder->relatedTo($claimValue);
                        break;
                    case LtiMessagePayloadInterface::CLAIM_ISS:
                        $builder->issuedBy($claimValue);
                        break;
                    case LtiMessagePayloadInterface::CLAIM_AUD:
                        $builder->permittedFor($claimValue);
                        break;
                    default:
                        $builder->withClaim($claimName, $claimValue);
                }
            }

            $builder
                ->withHeader(MessagePayloadInterface::HEADER_KID, $keyChain->getIdentifier())
                ->identifiedBy(Uuid::uuid4()->toString())
                ->issuedAt($now->toDateTimeImmutable())
                ->canOnlyBeUsedAfter($now->toDateTimeImmutable())
                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->toDateTimeImmutable())
                ->withClaim(MessagePayloadInterface::CLAIM_NONCE, $this->generator->generate()->getValue());

            return $builder->getToken($config->signer(), $config->signingKey());

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate message token: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
