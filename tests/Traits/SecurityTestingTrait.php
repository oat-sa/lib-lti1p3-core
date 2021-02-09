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

namespace OAT\Library\Lti1p3Core\Tests\Traits;

use Carbon\Carbon;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;

trait SecurityTestingTrait
{
    private function createTestKeyChain(
        string $identifier = 'keyChainIdentifier',
        string $keySetName = 'keySetName',
        string $publicKey = null,
        string $privateKey = null,
        string $privateKeyPassPhrase = null
    ): KeyChainInterface {
        return (new KeyChainFactory)->create(
            $identifier,
            $keySetName,
            $publicKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/public.key',
            $privateKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/private.key',
            $privateKeyPassPhrase
        );
    }

    private function buildJwt(
        array $headers = [],
        array $claims = [],
        KeyInterface $key = null
    ): TokenInterface {
        return (new Builder)->build(
            $headers,
            $claims,
            $key ?? $this->createTestKeyChain()->getPrivateKey()
        );
    }

    private function parseJwt(string $tokenString): TokenInterface
    {
        return (new Parser())->parse($tokenString);
    }

    private function verifyJwt(TokenInterface $token, KeyInterface $key): bool
    {
        return (new Validator())->validate($token, $key);
    }

    private function createTestClientAssertion(RegistrationInterface $registration, array $scopes = []): string
    {
        $assertion =  $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_ISS => $registration->getTool()->getAudience(),
                MessagePayloadInterface::CLAIM_SUB => $registration->getClientId(),
                MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        return $assertion->toString();
    }

    private function createTestClientAccessToken(RegistrationInterface $registration, array $scopes = []): string
    {
        $accessToken =  $this->buildJwt(
            [],
            [
                MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                'scopes' => $scopes
            ],
            $registration->getPlatformKeyChain()->getPrivateKey()
        );

        return $accessToken->toString();
    }

    private function createTestUserAuthenticator(
        bool $withAuthenticationSuccess = true,
        bool $withAnonymous = false
    ): UserAuthenticatorInterface {
        return new class ($withAuthenticationSuccess, $withAnonymous) implements UserAuthenticatorInterface
        {
            use DomainTestingTrait;

            /** @var bool */
            private $withAuthenticationSuccess;

            /** @var bool */
            private $withAnonymous;

            public function __construct(bool $withAuthenticationSuccess, bool $withAnonymous)
            {
                $this->withAuthenticationSuccess = $withAuthenticationSuccess;
                $this->withAnonymous = $withAnonymous;
            }

            public function authenticate(string $loginHint): UserAuthenticationResultInterface
            {
                return new UserAuthenticationResult(
                    $this->withAuthenticationSuccess,
                    $this->withAnonymous ? null : $this->createTestUserIdentity()
                );
            }
        };
    }

    private function createTestNonceRepository(array $nonces = [], bool $withAutomaticFind = false): NonceRepositoryInterface
    {
        $nonces = !empty($nonces) ? $nonces : [
            new Nonce('existing'),
            new Nonce('expired', Carbon::now()->subDay()),
        ];

        return new class ($nonces, $withAutomaticFind) implements NonceRepositoryInterface
        {
            /** @var NonceInterface[] */
            private $nonces;

            /** @var bool */
            private $withAutomaticFind;

            public function __construct(array $nonces, bool $withAutomaticFind)
            {
                foreach ($nonces as $nonce) {
                    $this->add($nonce);
                }

                $this->withAutomaticFind = $withAutomaticFind;
            }

            public function add(NonceInterface $nonce): self
            {
                $this->nonces[$nonce->getValue()] = $nonce;

                return $this;
            }

            public function find(string $value): ?NonceInterface
            {
                if ($this->withAutomaticFind) {
                    return current($this->nonces);
                }

                return $this->nonces[$value] ?? null;
            }

            public function save(NonceInterface $nonce): void
            {
                return;
            }
        };
    }
}
