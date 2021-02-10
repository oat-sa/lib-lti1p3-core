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
use Lcobucci\JWT\Signer\Hmac\Sha384;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
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

        $this->authenticatorMock
            ->expects($this->once())
            ->method('authenticate')
            ->with('login_hint')
            ->willReturn(new UserAuthenticationResult(false));

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/init?%s', http_build_query([
                'lti_message_hint' => $messageHint->toString(),
                'login_hint' => 'login_hint'
            ]))
        );

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
            ->willThrowException(new \Exception('custom error'));

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
