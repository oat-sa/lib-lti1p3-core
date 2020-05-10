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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Service\Server\Factory;

use Cache\Adapter\PHPArray\ArrayCachePool;
use InvalidArgumentException;
use League\OAuth2\Server\AuthorizationServer;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Service\Server\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Service\Server\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ScopeRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class AuthorizationServerFactoryTest extends TestCase
{
    use DomainTestingTrait;

    /** @var AuthorizationServerFactory */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new AuthorizationServerFactory(
            new ClientRepository($this->createTestRegistrationRepository()),
            new AccessTokenRepository(new ArrayCachePool()),
            new ScopeRepository(),
            'encryptionKey'
        );
    }

    public function testCreate(): void
    {
        $result = $this->subject->create($this->createTestKeyChain());

        $this->assertInstanceOf(AuthorizationServer::class, $result);
    }

    public function testCreateForRegistrationWithMissingPrivateKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing private key');

        $invalidKeyChain = new KeyChain(
            'identifier',
            'setName',
            $publicKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/RSA/public.key'
        );

        $this->subject->create($invalidKeyChain);
    }
}
