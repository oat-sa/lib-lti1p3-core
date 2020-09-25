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
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
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
    ): KeyChain {
        return new KeyChain(
            $identifier,
            $keySetName,
            $publicKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/RSA/public.key',
            $privateKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/RSA/private.key',
            $privateKeyPassPhrase
        );
    }

    private function buildJwt(
        array $headers = [],
        array $claims = [],
        Key $key = null,
        Signer $signer = null
    ): Token {
        $builder = new Builder();

        foreach ($headers as $headerName => $headerValue) {
            $builder->withHeader($headerName, $headerValue);
        }

        foreach ($claims as $claimName => $claimValue) {
            $builder->withClaim($claimName, $claimValue);
        };

        $builder->expiresAt(Carbon::now()->addSeconds(MessagePayloadInterface::TTL)->getTimestamp());

        return $builder->getToken(
            $signer ?? new Sha256(),
            $key ?? $this->createTestKeyChain()->getPrivateKey()
        );
    }

    private function parseJwt(string $tokenString): Token
    {
        return (new Parser(new AssociativeDecoder()))->parse($tokenString);
    }

    private function verifyJwt(Token $token, Key $key): bool
    {
        return $token->verify(new Sha256(), $key);
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
            /** @var NonceRepositoryInterface */
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
