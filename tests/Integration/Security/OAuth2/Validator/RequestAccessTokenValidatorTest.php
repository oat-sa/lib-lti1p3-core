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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\OAuth2\Validator;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidator;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResult;
use OAT\Library\Lti1p3Core\Tests\Resource\Logger\TestLogger;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class RequestAccessTokenValidatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var TestLogger */
    private $logger;

    /** @var RequestAccessTokenValidator */
    private $subject;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();

        $registrations = [
            $this->createTestRegistration(),
            $this->createTestRegistrationWithoutPlatformKeyChain(
                'missingKeyIdentifier',
                'missingKeyClientId'
            )
        ];

        $this->subject = new RequestAccessTokenValidator(
            $this->createTestRegistrationRepository($registrations),
            $this->logger
        );
    }

    public function testValidation(): void
    {
        $registration = $this->createTestRegistration();

        $request = $this->createServerRequest(
            'GET',
            '/example',
            [],
            ['Authorization' => 'Bearer ' . $this->generateTestClientAccessToken($registration, ['allowed-scope'])]
        );

        $result = $this->subject->validate($request, ['allowed-scope']);

        $this->assertInstanceOf(RequestAccessTokenValidationResult::class, $result);
        $this->assertFalse($result->hasError());
        $this->assertNull($result->getError());
        $this->assertEquals(
            [
                'Registration found for client_id: registrationClientId',
                'Platform key chain found for registration: registrationIdentifier',
                'JWT access token is valid',
                'JWT access token scopes are valid',
            ],
            $result->getSuccesses()
        );
        $this->assertEquals($registration->getClientId(), current($result->getToken()->getClaims()->get('aud')));
    }

    public function testValidationFailureWithMissingAuthorizationHeader(): void
    {
        $result = $this->subject->validate($this->createServerRequest('GET', '/example', []));

        $this->assertInstanceOf(RequestAccessTokenValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('Missing Authorization header', $result->getError());

        $this->assertTrue(
            $this->logger->hasLog(
                LogLevel::ERROR,
                'Access token validation error: Missing Authorization header'
            )
        );
    }

    public function testValidationFailureWithInvalidAudience(): void
    {
        $registration = $this->createTestRegistration();

        $request = $this->createServerRequest(
            'GET',
            '/example',
            [],
            ['Authorization' => 'Bearer ' . $this->generateTestClientAccessToken($registration, ['allowed-scope'], 'invalid')]
        );

        $result = $this->subject->validate($request);

        $this->assertInstanceOf(RequestAccessTokenValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('No registration found with client_id for audience(s) invalid', $result->getError());

        $this->assertTrue(
            $this->logger->hasLog(
                LogLevel::ERROR,
                'Access token validation error: No registration found with client_id for audience(s) invalid'
            )
        );
    }

    public function testValidationFailureWithInvalidRegistrationPlatformKeyChain(): void
    {
        $registration = $this->createTestRegistration();

        $request = $this->createServerRequest(
            'GET',
            '/example',
            [],
            ['Authorization' => 'Bearer ' . $this->generateTestClientAccessToken($registration, ['allowed-scope'], 'missingKeyClientId')]
        );

        $result = $this->subject->validate($request);

        $this->assertInstanceOf(RequestAccessTokenValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('Missing platform key chain for registration: missingKeyIdentifier', $result->getError());

        $this->assertTrue(
            $this->logger->hasLog(
                LogLevel::ERROR,
                'Access token validation error: Missing platform key chain for registration: missingKeyIdentifier'
            )
        );
    }

    public function testValidationFailureWithInvalidToken(): void
    {
        $registration = $this->createTestRegistration();

        $now = Carbon::now();
        Carbon::setTestNow($now->subHour());

        $request = $this->createServerRequest(
            'GET',
            '/example',
            [],
            ['Authorization' => 'Bearer ' . $this->generateTestClientAccessToken($registration, ['allowed-scope'])]
        );

        Carbon::setTestNow();

        $result = $this->subject->validate($request);

        $this->assertInstanceOf(RequestAccessTokenValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT access token is invalid', $result->getError());

        $this->assertTrue(
            $this->logger->hasLog(LogLevel::ERROR, 'Access token validation error: JWT access token is invalid')
        );
    }

    public function testValidationFailureWithInvalidScopes(): void
    {
        $registration = $this->createTestRegistration();

        $request = $this->createServerRequest(
            'GET',
            '/example',
            [],
            ['Authorization' => 'Bearer ' . $this->generateTestClientAccessToken($registration, ['invalid-scope'])]
        );

        $result = $this->subject->validate($request, ['allowed-scope']);

        $this->assertInstanceOf(RequestAccessTokenValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT access token scopes are invalid', $result->getError());

        $this->assertTrue(
            $this->logger->hasLog(
                LogLevel::ERROR,
                'Access token validation error: JWT access token scopes are invalid'
            )
        );
    }

    private function generateTestClientAccessToken(
        RegistrationInterface $registration,
        array $scopes = [],
        string $audience = null
    ): string {
        $accessToken = $this->buildJwt(
            [],
            [
                MessagePayloadInterface::CLAIM_AUD => $audience ?? $registration->getClientId(),
                'scopes' => $scopes
            ],
            $registration->getPlatformKeyChain()->getPrivateKey()
        );

        return $accessToken->toString();
    }
}
