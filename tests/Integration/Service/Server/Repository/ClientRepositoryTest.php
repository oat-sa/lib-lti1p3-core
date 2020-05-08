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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Service\Server\Repository;

use Carbon\Carbon;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Service\Server\Entity\Client;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Tests\Resource\Logger\TestLogger;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class ClientRepositoryTest extends TestCase
{
    use DomainTestingTrait;

    /** @var JwksFetcherInterface|MockObject */
    private $fetcherMock;

    /** @var TestLogger */
    private $logger;

    /** @var ClientRepository */
    private $subject;

    protected function setUp(): void
    {
        $this->fetcherMock = $this->createMock(JwksFetcherInterface::class);
        $this->logger = new TestLogger();

        $registrations = [
            $this->createTestRegistration(),
            $this->createTestRegistrationWithoutToolKeyChain('InvalidIdentifier', 'invalidClientId')
        ];

        $this->subject = new ClientRepository(
            $this->createTestRegistrationRepository($registrations),
            $this->fetcherMock,
            $this->logger
        );
    }

    public function testGetClientEntity(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->getClientEntity($registration->getClientId());

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($registration->getClientId(), $result->getIdentifier());

        $this->assertNull($this->subject->getClientEntity('invalid'));
    }

    public function testValidateClientFailureWithInvalidGrantType(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->validateClient($registration->getClientId(), 'invalid', 'invalid');

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Unhandled grant type: invalid'));
    }

    public function testValidateClientFailureWithInvalidSecret(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->validateClient(
            $registration->getClientId(),
            'invalid',
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertFalse($result);
        $this->assertTrue(
            $this->logger->hasLog(
                LogLevel::ERROR,
                'Cannot parse the client_assertion JWT: The JWT string must have two dots'
            )
        );
    }

    public function testValidateClientFailureWithExpiredJWT(): void
    {
        $registration = $this->createTestRegistration();

        $now = Carbon::now();
        Carbon::setTestNow($now->subHour());

        $secret = $this->generateClientAssertion($registration);

        Carbon::setTestNow();

        $result = $this->subject->validateClient(
            $registration->getClientId(),
            $secret,
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'The client_assertion JWT is expired'));
    }

    public function testValidateClientFailureWithInvalidIdentifier(): void
    {
        $registration = $this->createTestRegistration();

        $secret = $this->generateClientAssertion($registration);

        $result = $this->subject->validateClient(
            'invalid',
            $secret,
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Cannot find registration for client_id: invalid'));
    }

    public function testValidateClientFailureWithInvalidAudience(): void
    {
        $registration = $this->createTestRegistration();

        $secret = $this->generateClientAssertion($registration, 'invalid');

        $result = $this->subject->validateClient(
            $registration->getClientId(),
            $secret,
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Invalid audience: invalid'));
    }

    public function testValidateClientFailureWithJWKSFetchError(): void
    {
        $registration = $this->createTestRegistrationWithoutToolKeyChain(
            'InvalidIdentifier',
            'invalidClientId'
        );

        $secret = $this->generateClientAssertion($this->createTestRegistration());

        $this->fetcherMock
            ->expects($this->once())
            ->method('fetchKey')
            ->willThrowException(new Exception('JWKS fetch error'));

        $result = $this->subject->validateClient(
            $registration->getClientId(),
            $secret,
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Cannot find tool public key: JWKS fetch error'));
    }

    public function testValidateClientFailureWithJWKSSignatureError(): void
    {
        $registration = $this->createTestRegistrationWithoutToolKeyChain(
            'InvalidIdentifier',
            'invalidClientId'
        );

        $secret = $this->generateClientAssertion($this->createTestRegistration());

        $this->fetcherMock
            ->expects($this->once())
            ->method('fetchKey')
            ->willReturn(new Key('file://' . __DIR__ . '/../../../../Resource/Key/RSA/public2.key'));

        $result = $this->subject->validateClient(
            $registration->getClientId(),
            $secret,
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Invalid JWT signature'));
    }

    private function generateClientAssertion(RegistrationInterface $registration, string $audience = null): string
    {
        $now = Carbon::now();

        return (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
            ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getPreciseTimestamp()))
            ->issuedBy($registration->getTool()->getAudience())
            ->relatedTo($registration->getClientId())
            ->permittedFor($audience ?? $registration->getPlatform()->getOAuth2AccessTokenUrl())
            ->issuedAt($now->getTimestamp())
            ->expiresAt($now->addSeconds(MessageInterface::TTL)->getTimestamp())
            ->getToken(new Sha256(), $registration->getToolKeyChain()->getPrivateKey())
            ->__toString();
    }
}
