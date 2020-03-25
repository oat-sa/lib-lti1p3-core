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

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;

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

    private function createTestNonceRepository(bool $withNonceFind = false): NonceRepositoryInterface
    {
        return new class ($withNonceFind) implements NonceRepositoryInterface
        {
            /** @var bool */
            private $withNonceFind;

            public function __construct(bool $withNonceFind)
            {
                $this->withNonceFind = $withNonceFind;
            }

            public function find(string $value): ?NonceInterface
            {
                return $this->withNonceFind ? new Nonce('value') : null;
            }

            public function save(NonceInterface $nonce): void
            {
                return;
            }
        };
    }
}
