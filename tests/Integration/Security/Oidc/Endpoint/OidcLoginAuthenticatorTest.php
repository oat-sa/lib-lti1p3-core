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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\Oidc\Endpoint;

use Carbon\Carbon;
use Lcobucci\JWT\Signer\Hmac\Sha384;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\LtiMessage;
use OAT\Library\Lti1p3Core\Token\LtiMessageToken;
use OAT\Library\Lti1p3Core\Token\MessageInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginInitiator;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class OidcLoginAuthenticatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    public function testAuthenticationSuccess(): void
    {
        $registrationRepository = $this->createTestRegistrationRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $subject = new OidcAuthenticator($registrationRepository, $this->createTestUserAuthenticator());

        $resourceLink = $this->createTestResourceLink();
        $registration = $this->createTestRegistration();

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $resourceLink,
            $registration,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $result = $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $idToken = $this->parseJwt($result->getLtiMessage());

        $this->assertInstanceOf(LtiMessage::class, $result);

        $this->assertEquals($resourceLink->getUrl(), $result->getUrl());

        $this->assertTrue($this->verifyJwt($idToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertTrue($this->verifyJwt(
            $this->parseJwt($result->getOidcState()),
            $registration->getToolKeyChain()->getPublicKey()
        ));

        $ltiMessage = new LtiMessageToken($idToken);
        $this->assertEquals($this->createTestUserIdentity(), $ltiMessage->getUserIdentity());
    }

    public function testAnonymousAuthenticationSuccess(): void
    {
        $registrationRepository = $this->createTestRegistrationRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $subject = new OidcAuthenticator($registrationRepository, $this->createTestUserAuthenticator(true, true));

        $resourceLink = $this->createTestResourceLink();
        $registration = $this->createTestRegistration();

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $resourceLink,
            $registration,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $result = $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $idToken = $this->parseJwt($result->getLtiMessage());

        $this->assertInstanceOf(LtiMessage::class, $result);

        $this->assertEquals($resourceLink->getUrl(), $result->getUrl());

        $this->assertTrue($this->verifyJwt($idToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertTrue($this->verifyJwt(
            $this->parseJwt($result->getOidcState()),
            $registration->getToolKeyChain()->getPublicKey()
        ));

        $ltiMessage = new LtiMessageToken($idToken);
        $this->assertNull($ltiMessage->getUserIdentity());
    }

    public function testAuthenticationFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('User authentication failure');

        $registrationRepository = $this->createTestRegistrationRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $subject = new OidcAuthenticator($registrationRepository, $this->createTestUserAuthenticator(false));

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );
    }

    public function testFailureOnInvalidMessageHintRegistrationId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid message hint registration id claim');

        $registrationRepository = $this->createTestRegistrationRepository([$this->createMock(RegistrationInterface::class)]);
        $oidcLoginInitiator = new OidcLoginInitiator($this->createTestRegistrationRepository());

        $subject = new OidcAuthenticator($registrationRepository, $this->createTestUserAuthenticator());

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );
    }

    public function testFailureOnInvalidMessageHintSignature(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid message hint signature');

        $registrationRepository = $this->createTestRegistrationRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);

        $subject = new OidcAuthenticator(
            $registrationRepository,
            $this->createTestUserAuthenticator(),
            null,
            new Sha384()
        );

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );
    }

    public function testFailureOnInvalidExpiredMessageHint(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Message hint expired');

        $now = Carbon::now();

        Carbon::setTestNow($now->subSeconds(MessageInterface::TTL + 1));
        $registrationRepository = $this->createTestRegistrationRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);

        $subject = new OidcAuthenticator($registrationRepository, $this->createTestUserAuthenticator(false));

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        Carbon::setTestNow();

        $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );
    }

    public function testItThrowAnLtiExceptionOnGenericError(): void
    {
        $this->expectException(LtiException::class);

        $subject = new OidcAuthenticator(
            $this->createTestRegistrationRepository(),
            $this->createTestUserAuthenticator()
        );

        $subject->authenticate($this->createServerRequest('GET', 'url'));
    }
}
