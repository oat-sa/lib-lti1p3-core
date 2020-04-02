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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Launch\Validator;

use Carbon\Carbon;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha384;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidationResult;
use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidator;
use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginInitiator;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiLaunchRequestValidatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    public function testValidationSuccessOnAnonymousLtiLaunchRequest(): void
    {
        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasFailures());
        $this->assertEmpty($result->getFailures());
        $this->assertEquals(
            [
                'JWT id_token signature validation success',
                'JWT id_token is not expired',
                'JWT id_token nonce is valid',
                'JWT id_token iss claim matches platform audience',
                'JWT id_token aud claim matches tool oauth2 client id'
            ],
            $result->getSuccesses()
        );

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertNull($result->getLtiMessage()->getUserIdentity());
    }

    public function testValidationSuccessOnUserLtiLaunchRequest(): void
    {
        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildUserResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            $this->createTestUserIdentity()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasFailures());
        $this->assertEmpty($result->getFailures());
        $this->assertEquals(
            [
                'JWT id_token signature validation success',
                'JWT id_token is not expired',
                'JWT id_token nonce is valid',
                'JWT id_token iss claim matches platform audience',
                'JWT id_token aud claim matches tool oauth2 client id'
            ],
            $result->getSuccesses()
        );

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertEquals(
            $this->createTestUserIdentity(),
            $result->getLtiMessage()->getUserIdentity()
        );
    }

    public function testValidationSuccessOnAnonymousOidcLaunchRequest(): void
    {
        $registrationRepository = $this->createTestRegistrationRepository();

        $subject = new LtiLaunchRequestValidator(
            $registrationRepository,
            $this->createTestNonceRepository()
        );

        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $oidcLoginAuthenticator = new OidcLoginAuthenticator(
            $registrationRepository,
            $this->createTestUserAuthenticator(true, true)
        );

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $launchRequest = $oidcLoginAuthenticator->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasFailures());
        $this->assertEmpty($result->getFailures());
        $this->assertEquals(
            [
                'JWT id_token signature validation success',
                'JWT id_token is not expired',
                'JWT id_token nonce is valid',
                'JWT id_token iss claim matches platform audience',
                'JWT id_token aud claim matches tool oauth2 client id',
                'JWT OIDC state signature validation success',
                'JWT OIDC state is not expired'
            ],
            $result->getSuccesses()
        );

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertNull($result->getLtiMessage()->getUserIdentity());
    }

    public function testValidationSuccessOnOidcLaunchRequest(): void
    {
        $registrationRepository = $this->createTestRegistrationRepository();

        $subject = new LtiLaunchRequestValidator(
            $registrationRepository,
            $this->createTestNonceRepository()
        );

        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $oidcLoginAuthenticator = new OidcLoginAuthenticator($registrationRepository, $this->createTestUserAuthenticator());

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $launchRequest = $oidcLoginAuthenticator->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasFailures());
        $this->assertEmpty($result->getFailures());
        $this->assertEquals(
            [
                'JWT id_token signature validation success',
                'JWT id_token is not expired',
                'JWT id_token nonce is valid',
                'JWT id_token iss claim matches platform audience',
                'JWT id_token aud claim matches tool oauth2 client id',
                'JWT OIDC state signature validation success',
                'JWT OIDC state is not expired'
            ],
            $result->getSuccesses()
        );

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertEquals(
            $this->createTestUserIdentity(),
            $result->getLtiMessage()->getUserIdentity()
        );
    }

    public function testItFallsBackOnJwksFetcherWhenPlatformPublicKeyIsNotConfigured(): void
    {
        $keyChain = $this->createTestKeyChain('platformKeyChain');

        $registration = $this->createTestRegistrationWithJwksPlatform();

        $jwksFetcherMock = $this->createMock(JwksFetcherInterface::class);
        $jwksFetcherMock
            ->expects($this->once())
            ->method('fetchKey')
            ->with('http://platform.com/jwks', 'platformKeyChain')
            ->willReturn($keyChain->getPublicKey());

        $registrationRepository = $this->createTestRegistrationRepository([$registration]);

        $subject = new LtiLaunchRequestValidator(
            $registrationRepository,
            $this->createTestNonceRepository(),
            $jwksFetcherMock
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasFailures());
    }

    public function testValidationFailureOnInvalidIdTokenSignature(): void
    {
        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository(),
            null,
            new Sha384()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(['JWT id_token signature validation failure'], $result->getFailures());
    }

    public function testValidationFailureOnExpiredIdToken(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now->subSeconds(MessageInterface::TTL + 1));

        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        Carbon::setTestNow();

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(['JWT id_token is expired'], $result->getFailures());
    }

    public function testValidationFailureOnAlreadyUsedNonce(): void
    {
        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository(true)
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(['JWT id_token nonce already used'], $result->getFailures());
    }

    public function testValidationFailureOnIMissingToolKeyChain(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Tool key chain not configured');

        $state = (new Builder())->getToken(new Sha256(), $this->createTestKeyChain()->getPrivateKey())->__toString();

        $registration = $this->createTestRegistrationWithoutToolKeyChain();

        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository([$registration]),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $registration,
            $registration->getDefaultDeploymentId(),
            [],
            [],
            $state
        );

        $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));
    }

    public function testValidationFailureOnInvalidOidcStateSignature(): void
    {
        $state = (new Builder())->getToken(new Sha384(), $this->createTestKeyChain()->getPrivateKey())->__toString();

        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            null,
            [],
            [],
            $state
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(['JWT OIDC state signature validation failure'], $result->getFailures());
    }

    public function testValidationFailureOnExpiredOidcState(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now->subSeconds(MessageInterface::TTL + 1));

        $state = (new MessageBuilder())
            ->getMessage($this->createTestKeyChain())
            ->getToken()
            ->__toString();

        Carbon::setTestNow();

        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            null,
            [],
            [],
            $state
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasFailures());
        $this->assertEquals(['JWT OIDC state is expired'], $result->getFailures());
    }

    public function testItThrowAnLtiExceptionOnNotFoundRegistration(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('no matching registration found');

        $subject = new LtiLaunchRequestValidator(
            $this->createTestRegistrationRepository([$this->createMock(RegistrationInterface::class)]),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));
    }

    public function testItThrowAnLtiExceptionOnGenericError(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('LTI message validation failed: custom error');

        $registration = $this->createTestRegistration();

        $registrationRepositoryMock = $this->createMock(RegistrationRepositoryInterface::class);
        $registrationRepositoryMock
            ->expects($this->once())
            ->method('findByPlatformIssuer')
            ->willThrowException(new Exception('custom error'));

        $subject = new LtiLaunchRequestValidator($registrationRepositoryMock, $this->createTestNonceRepository());

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $registration
        );

        $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));
    }
}
