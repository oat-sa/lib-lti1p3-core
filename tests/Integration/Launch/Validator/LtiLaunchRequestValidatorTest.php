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
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use OAT\Library\Lti1p3Core\Token\Claim\ResourceLinkTokenClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Validator\LtiResourceLinkLaunchRequestValidationResult;
use OAT\Library\Lti1p3Core\Launch\Validator\LtiResourceLinkLaunchRequestValidator;
use OAT\Library\Lti1p3Core\Token\Builder\MessageTokenBuilder;
use OAT\Library\Lti1p3Core\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Token\MessageInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcAuthenticator;
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
        $registration = $this->createTestRegistration();

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $registration
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasError());
        $this->assertNull($result->getError());
        $this->assertEquals(
            [
                'JWT id_token is not expired',
                'JWT id_token kid header is provided',
                'JWT id_token version claim is valid',
                'JWT id_token message_type claim is valid',
                'JWT id_token roles claim is valid',
                'JWT id_token resource_link id claim is valid',
                'JWT id_token user identifier (sub) claim is valid',
                'JWT id_token signature validation success',
                'JWT id_token nonce is valid',
                'JWT id_token deployment_id claim valid for this registration',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals($registration->getIdentifier(), $result->getRegistration()->getIdentifier());

        $this->assertEquals(LtiMessageTokenInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertNull($result->getLtiMessage()->getUserIdentity());
    }

    public function testValidationSuccessOnUserLtiLaunchRequest(): void
    {
        $registration = $this->createTestRegistration();

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildUserResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $registration,
            $this->createTestUserIdentity()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasError());
        $this->assertNull($result->getError());
        $this->assertEquals(
            [
                'JWT id_token is not expired',
                'JWT id_token kid header is provided',
                'JWT id_token version claim is valid',
                'JWT id_token message_type claim is valid',
                'JWT id_token roles claim is valid',
                'JWT id_token resource_link id claim is valid',
                'JWT id_token user identifier (sub) claim is valid',
                'JWT id_token signature validation success',
                'JWT id_token nonce is valid',
                'JWT id_token deployment_id claim valid for this registration',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals($registration->getIdentifier(), $result->getRegistration()->getIdentifier());

        $this->assertEquals(LtiMessageTokenInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
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
        $registration = $this->createTestRegistration();
        $registrationRepository = $this->createTestRegistrationRepository();

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $registrationRepository,
            $this->createTestNonceRepository()
        );

        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $oidcLoginAuthenticator = new OidcAuthenticator(
            $registrationRepository,
            $this->createTestUserAuthenticator(true, true)
        );

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $registration,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $launchRequest = $oidcLoginAuthenticator->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasError());
        $this->assertNull($result->getError());
        $this->assertEquals(
            [
                'JWT id_token is not expired',
                'JWT id_token kid header is provided',
                'JWT id_token version claim is valid',
                'JWT id_token message_type claim is valid',
                'JWT id_token roles claim is valid',
                'JWT id_token resource_link id claim is valid',
                'JWT id_token user identifier (sub) claim is valid',
                'JWT id_token signature validation success',
                'JWT id_token nonce is valid',
                'JWT id_token deployment_id claim valid for this registration',
                'JWT OIDC state is not expired',
                'JWT OIDC state signature validation success',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals($registration->getIdentifier(), $result->getRegistration()->getIdentifier());

        $this->assertEquals(LtiMessageTokenInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertNull($result->getLtiMessage()->getUserIdentity());
    }

    public function testValidationSuccessOnOidcLaunchRequest(): void
    {
        $registration = $this->createTestRegistration();
        $registrationRepository = $this->createTestRegistrationRepository();

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $registrationRepository,
            $this->createTestNonceRepository()
        );

        $oidcLoginInitiator = new OidcLoginInitiator($registrationRepository);
        $oidcLoginAuthenticator = new OidcAuthenticator($registrationRepository, $this->createTestUserAuthenticator());

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $registration,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $launchRequest = $oidcLoginAuthenticator->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasError());
        $this->assertNull($result->getError());
        $this->assertEquals(
            [
                'JWT id_token is not expired',
                'JWT id_token kid header is provided',
                'JWT id_token version claim is valid',
                'JWT id_token message_type claim is valid',
                'JWT id_token roles claim is valid',
                'JWT id_token resource_link id claim is valid',
                'JWT id_token user identifier (sub) claim is valid',
                'JWT id_token signature validation success',
                'JWT id_token nonce is valid',
                'JWT id_token deployment_id claim valid for this registration',
                'JWT OIDC state is not expired',
                'JWT OIDC state signature validation success',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals($registration->getIdentifier(), $result->getRegistration()->getIdentifier());

        $this->assertEquals(LtiMessageTokenInterface::LTI_VERSION, $result->getLtiMessage()->getVersion());
        $this->assertEquals(
            $this->createTestResourceLink()->getIdentifier(),
            $result->getLtiMessage()->getResourceLink()->getId()
        );
        $this->assertEquals(
            $this->createTestUserIdentity(),
            $result->getLtiMessage()->getUserIdentity()
        );
    }

    public function testValidationSuccessOnAlreadyUsedNonceButExpired(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository(
                [new Nonce('value', Carbon::now()->subSeconds(NonceGeneratorInterface::TTL + 1))],
                true
            )
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasError());
        $this->assertNull($result->getError());
        $this->assertEquals(
            [
                'JWT id_token is not expired',
                'JWT id_token kid header is provided',
                'JWT id_token version claim is valid',
                'JWT id_token message_type claim is valid',
                'JWT id_token roles claim is valid',
                'JWT id_token resource_link id claim is valid',
                'JWT id_token user identifier (sub) claim is valid',
                'JWT id_token signature validation success',
                'JWT id_token nonce already used but expired',
                'JWT id_token deployment_id claim valid for this registration',
            ],
            $result->getSuccesses()
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

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $registrationRepository,
            $this->createTestNonceRepository(),
            $jwksFetcherMock
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertFalse($result->hasError());
    }

    public function testValidationFailureOnInvalidIdTokenSignature(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
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

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token signature validation failure', $result->getError());
    }

    public function testValidationFailureOnInvalidDeploymentId(): void
    {
        $invalidRegistration = $this->createTestRegistration(
            'registrationIdentifier',
            'registrationClientId',
            $this->createTestPlatform(),
            $this->createTestTool(),
            ['invalid']
        );

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository([$invalidRegistration]),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token deployment_id claim not valid for this registration', $result->getError());
    }

    public function testValidationFailureOnExpiredIdToken(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now->subSeconds(MessageInterface::TTL + 1));

        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        Carbon::setTestNow();

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token is expired', $result->getError());
    }

    public function testValidationFailureOnAlreadyUsedNonce(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository([new Nonce('value')], true)
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token nonce already used', $result->getError());
    }

    public function testValidationFailureOnMissingKid(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $registration = $this->createTestRegistration();

        $token = (new Builder())
            ->withClaim(MessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
            ->withClaim(MessageInterface::CLAIM_AUD, $registration->getClientId())
            ->getToken();

        $result = $subject->validate($this->createServerRequest(
            'GET',
            sprintf('http://tool.com/launch?id_token=%s', $token->__toString())
        ));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token kid header is missing', $result->getError());
    }

    public function testValidationFailureOnInvalidVersion(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $registration = $this->createTestRegistration();

        $token = (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, 'kid')
            ->withClaim(MessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
            ->withClaim(MessageInterface::CLAIM_AUD, $registration->getClientId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_VERSION, 'invalid')
            ->getToken();

        $result = $subject->validate($this->createServerRequest(
            'GET',
            sprintf('http://tool.com/launch?id_token=%s', $token->__toString())
        ));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token version claim is invalid', $result->getError());
    }

    public function testValidationFailureOnInvalidMessageType(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $registration = $this->createTestRegistration();

        $token = (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, 'kid')
            ->withClaim(MessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
            ->withClaim(MessageInterface::CLAIM_AUD, $registration->getClientId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_VERSION, LtiMessageTokenInterface::LTI_VERSION)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_MESSAGE_TYPE, '')
            ->getToken();

        $result = $subject->validate($this->createServerRequest(
            'GET',
            sprintf('http://tool.com/launch?id_token=%s', $token->__toString())
        ));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token message_type claim is invalid', $result->getError());
    }

    public function testValidationFailureOnInvalidRoles(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $registration = $this->createTestRegistration();

        $token = (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, 'kid')
            ->withClaim(MessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
            ->withClaim(MessageInterface::CLAIM_AUD, $registration->getClientId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_VERSION, LtiMessageTokenInterface::LTI_VERSION)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_MESSAGE_TYPE, ResourceLinkInterface::TYPE)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_ROLES, '')
            ->getToken();

        $result = $subject->validate($this->createServerRequest(
            'GET',
            sprintf('http://tool.com/launch?id_token=%s', $token->__toString())
        ));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token roles claim is invalid', $result->getError());
    }

    public function testValidationFailureOnInvalidResourceLinkId(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $registration = $this->createTestRegistration();

        $token = (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, 'kid')
            ->withClaim(MessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
            ->withClaim(MessageInterface::CLAIM_AUD, $registration->getClientId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_VERSION, LtiMessageTokenInterface::LTI_VERSION)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_MESSAGE_TYPE, ResourceLinkInterface::TYPE)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_RESOURCE_LINK, ['id' => ''])
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_DEPLOYMENT_ID, $registration->getDefaultDeploymentId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_NONCE, 'nonce')
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_ROLES, [])
            ->getToken(new Sha256(), $registration->getPlatformKeyChain()->getPrivateKey());

        $result = $subject->validate($this->createServerRequest(
            'GET',
            sprintf('http://tool.com/launch?id_token=%s', $token->__toString())
        ));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token resource_link id claim is invalid', $result->getError());
    }

    public function testValidationFailureOnInvalidUserIdentifier(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository(),
            $this->createTestNonceRepository()
        );

        $registration = $this->createTestRegistration();

        $token = (new Builder())
            ->withHeader(MessageInterface::HEADER_KID, 'kid')
            ->withClaim(MessageInterface::CLAIM_ISS, $registration->getPlatform()->getAudience())
            ->withClaim(MessageInterface::CLAIM_AUD, $registration->getClientId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_VERSION, LtiMessageTokenInterface::LTI_VERSION)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_MESSAGE_TYPE, ResourceLinkInterface::TYPE)
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_RESOURCE_LINK, (new ResourceLinkTokenClaim('identifier'))->normalize())
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_DEPLOYMENT_ID, $registration->getDefaultDeploymentId())
            ->withClaim(LtiMessageTokenInterface::CLAIM_NONCE, 'nonce')
            ->withClaim(LtiMessageTokenInterface::CLAIM_LTI_ROLES, [])
            ->withClaim(LtiMessageTokenInterface::CLAIM_SUB, '')
            ->getToken(new Sha256(), $registration->getPlatformKeyChain()->getPrivateKey());

        $result = $subject->validate($this->createServerRequest(
            'GET',
            sprintf('http://tool.com/launch?id_token=%s', $token->__toString())
        ));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT id_token user identifier (sub) claim is invalid', $result->getError());
    }

    public function testValidationFailureOnInvalidOidcStateSignature(): void
    {
        $state = (new Builder())->getToken(new Sha384(), $this->createTestKeyChain()->getPrivateKey())->__toString();

        $subject = new LtiResourceLinkLaunchRequestValidator(
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

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT OIDC state signature validation failure', $result->getError());
    }

    public function testValidationFailureOnExpiredOidcState(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now->subSeconds(MessageInterface::TTL + 1));

        $state = (new MessageTokenBuilder())
            ->getMessage($this->createTestKeyChain())
            ->getToken()
            ->__toString();

        Carbon::setTestNow();

        $subject = new LtiResourceLinkLaunchRequestValidator(
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

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT OIDC state is expired', $result->getError());
    }

    public function testItThrowAnLtiExceptionOnNotFoundRegistration(): void
    {
        $subject = new LtiResourceLinkLaunchRequestValidator(
            $this->createTestRegistrationRepository([$this->createMock(RegistrationInterface::class)]),
            $this->createTestNonceRepository()
        );

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration()
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('No matching registration found', $result->getError());
    }

    public function testItThrowAnLtiExceptionOnIMissingToolKeyChain(): void
    {
        $state = (new Builder())->getToken(new Sha256(), $this->createTestKeyChain()->getPrivateKey())->__toString();

        $registration = $this->createTestRegistrationWithoutToolKeyChain();

        $subject = new LtiResourceLinkLaunchRequestValidator(
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

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('Tool key chain not configured', $result->getError());
    }

    public function testItThrowAnLtiExceptionOnGenericError(): void
    {
        $registration = $this->createTestRegistration();

        $registrationRepositoryMock = $this->createMock(RegistrationRepositoryInterface::class);
        $registrationRepositoryMock
            ->expects($this->once())
            ->method('findByPlatformIssuer')
            ->willThrowException(new Exception('custom error'));

        $subject = new LtiResourceLinkLaunchRequestValidator($registrationRepositoryMock, $this->createTestNonceRepository());

        $launchRequest = (new LtiLaunchRequestBuilder())->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $registration
        );

        $result = $subject->validate($this->createServerRequest('GET', $launchRequest->toUrl()));

        $this->assertInstanceOf(LtiResourceLinkLaunchRequestValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('custom error', $result->getError());
    }
}
