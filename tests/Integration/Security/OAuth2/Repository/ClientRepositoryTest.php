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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\OAuth2\Repository;

use Carbon\Carbon;
use Exception;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Client;
use OAT\Library\Lti1p3Core\Security\OAuth2\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ClientRepository;
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

    public function testValidateClientSuccessWithAccessTokenUrlAsAudience(): void
    {
        $registration = $this->createTestRegistration();

        $secret = $this->generateClientAssertion($registration, $registration->getPlatform()->getOAuth2AccessTokenUrl());

        $result = $this->subject->validateClient(
            $registration->getClientId(),
            $secret,
            ClientAssertionCredentialsGrant::GRANT_IDENTIFIER
        );

        $this->assertTrue($result);
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
                'Cannot parse the client_assertion JWT: Cannot parse token: The JWT string must have two dots'
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
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Invalid client_assertion JWT'));
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
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Registration platform does not match audience(s): invalid'));
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
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Invalid client_assertion JWT'));
    }

    private function generateClientAssertion(RegistrationInterface $registration, string $audience = null): string
    {
        $assertion = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_ISS => $registration->getTool()->getAudience(),
                MessagePayloadInterface::CLAIM_SUB => $registration->getClientId(),
                MessagePayloadInterface::CLAIM_AUD => $audience ?? $registration->getPlatform()->getAudience(),
            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        return $assertion->toString();
    }
}
