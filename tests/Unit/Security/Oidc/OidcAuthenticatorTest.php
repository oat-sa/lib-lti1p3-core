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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Oidc;

use Carbon\Carbon;
use Exception;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcAuthenticatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var RegistrationRepositoryInterface|MockObject */
    private $repositoryMock;

    /** @var UserAuthenticatorInterface|MockObject
     */
    private $authenticatorMock;

    /** @var OidcAuthenticator */
    private $subject;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(RegistrationRepositoryInterface::class);
        $this->authenticatorMock = $this->createMock(UserAuthenticatorInterface::class);

        $this->subject= new OidcAuthenticator($this->repositoryMock, $this->authenticatorMock);
    }

    public function testAuthenticationSuccess(): void
    {
        $this->subject = new OidcAuthenticator($this->createTestRegistrationRepository(), $this->createTestUserAuthenticator());

        $registration = $this->createTestRegistration();

        $messageHint = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                LtiMessagePayloadInterface::CLAIM_LTI_TARGET_LINK_URI => 'target_link_uri',
                LtiMessagePayloadInterface::CLAIM_REGISTRATION_ID => $registration->getIdentifier(),

            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'login_hint' => 'login_hint',
                'lti_message_hint' => $messageHint->toString(),
                'state' => 'state'
            ]))
        );

        $result = $this->subject->authenticate($request);

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $this->assertTrue($result->getParameters()->has('id_token'));
        $this->assertTrue($result->getParameters()->has('state'));

        $idToken = $this->parseJwt($result->getParameters()->get('id_token'));

        $this->assertTrue($this->verifyJwt($idToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals(
            'target_link_uri',
            $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_TARGET_LINK_URI)
        );
        $this->assertEquals(
            $registration->getIdentifier(),
            $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_REGISTRATION_ID)
        );
    }

    public function testAuthenticationSuccessWithUserIdentityClaimsSanitization(): void
    {
        $this->subject = new OidcAuthenticator($this->createTestRegistrationRepository(), $this->createTestUserAuthenticator());

        $registration = $this->createTestRegistration();

        $messageHint = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                LtiMessagePayloadInterface::CLAIM_LTI_TARGET_LINK_URI => 'target_link_uri',
                LtiMessagePayloadInterface::CLAIM_REGISTRATION_ID => $registration->getIdentifier(),
                LtiMessagePayloadInterface::CLAIM_SUB => 'sub',
                LtiMessagePayloadInterface::CLAIM_USER_NAME => 'name',
                LtiMessagePayloadInterface::CLAIM_USER_EMAIL => 'email',
                LtiMessagePayloadInterface::CLAIM_USER_GIVEN_NAME => 'given_name',
                LtiMessagePayloadInterface::CLAIM_USER_FAMILY_NAME => 'family_name',
                LtiMessagePayloadInterface::CLAIM_USER_MIDDLE_NAME => 'middle_name',
                LtiMessagePayloadInterface::CLAIM_USER_LOCALE => 'locale',
                LtiMessagePayloadInterface::CLAIM_USER_PICTURE => 'picture',

            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'login_hint' => 'login_hint',
                'lti_message_hint' => $messageHint->toString(),
                'state' => 'state'
            ]))
        );

        $result = $this->subject->authenticate($request);

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $this->assertTrue($result->getParameters()->has('id_token'));

        $idToken = $this->parseJwt($result->getParameters()->get('id_token'));

        $this->assertTrue($this->verifyJwt($idToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals('userIdentifier', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_SUB));
        $this->assertEquals('userName', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_NAME));
        $this->assertEquals('userEmail', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_EMAIL));
        $this->assertEquals('userGivenName', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_GIVEN_NAME));
        $this->assertEquals('userFamilyName', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_FAMILY_NAME));
        $this->assertEquals('userMiddleName', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_MIDDLE_NAME));
        $this->assertEquals('userLocale', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_LOCALE));
        $this->assertEquals('userPicture', $idToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_USER_PICTURE));
    }

    public function testAuthenticationFailureOnExpiredMessageHint(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid message hint');

        $registration = $this->createTestRegistration();

        Carbon::setTestNow(Carbon::now()->subSeconds(MessagePayloadInterface::TTL + 1));
        $messageHint = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_REGISTRATION_ID => $registration->getIdentifier()
            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );
        Carbon::setTestNow();

        $this->repositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($registration->getIdentifier())
            ->willReturn($registration);

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'lti_message_hint' => $messageHint->toString(),
            ]))
        );

        $this->subject->authenticate($request);
    }

    public function testAuthenticationFailureOnRegistrationNotFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid message hint registration id claim');

        $registration = $this->createTestRegistration();

        $messageHint = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_REGISTRATION_ID => $registration->getIdentifier()
            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        $this->repositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($registration->getIdentifier())
            ->willReturn(null);

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'lti_message_hint' => $messageHint->toString(),
            ]))
        );

        $this->subject->authenticate($request);
    }

    public function testAuthenticationFailureOnAuthenticationFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('User authentication failure');

        $registration = $this->createTestRegistration();

        $messageHint = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_REGISTRATION_ID => $registration->getIdentifier()
            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        $this->repositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($registration->getIdentifier())
            ->willReturn($registration);

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'lti_message_hint' => $messageHint->toString(),
                'login_hint' => 'login_hint'
            ]))
        );

        $this->authenticatorMock
            ->expects($this->once())
            ->method('authenticate')
            ->with($registration, 'login_hint')
            ->willReturn(new UserAuthenticationResult(false));

        $this->subject->authenticate($request);
    }

    public function testAuthenticationFailureOnGenericError(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('OIDC authentication failed: custom error');

        $registration = $this->createTestRegistration();

        $messageHint = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_REGISTRATION_ID => $registration->getIdentifier()
            ],
            $registration->getToolKeyChain()->getPrivateKey()
        );

        $this->repositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($registration->getIdentifier())
            ->willThrowException(new Exception('custom error'));

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'lti_message_hint' => $messageHint->toString(),
                'login_hint' => 'login_hint'
            ]))
        );

        $this->subject->authenticate($request);
    }
}
