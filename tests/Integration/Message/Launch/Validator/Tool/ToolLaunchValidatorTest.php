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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Message\Launch\Validator\Tool;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResultInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidator;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Tests\Traits\OidcTestingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;

class ToolLaunchValidatorTest extends TestCase
{
    use OidcTestingTrait;

    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var NonceRepositoryInterface */
    private $nonceRepository;

    /** @var RegistrationInterface */
    private $registration;

    /** @var PlatformOriginatingLaunchBuilder */
    private $builder;

    /** @var OidcInitiator */
    private $oidcInitiator;

    /** @var OidcAuthenticator */
    private $oidcAuthenticator;

    /** @var ToolLaunchValidator */
    private $subject;

    protected function setUp(): void
    {
        $this->registrationRepository = $this->createTestRegistrationRepository();
        $this->nonceRepository = $this->createTestNonceRepository();

        $this->registration = $this->createTestRegistration();

        $this->builder = new PlatformOriginatingLaunchBuilder();
        $this->oidcInitiator = new OidcInitiator($this->registrationRepository);
        $this->oidcAuthenticator = new OidcAuthenticator($this->registrationRepository, $this->createTestUserAuthenticator());

        $this->subject = new ToolLaunchValidator($this->registrationRepository, $this->nonceRepository);
    }

    public function testGetSupportedMessageTypes(): void
    {
        $this->assertEquals(
            [
                LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
                LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
                LtiMessageInterface::LTI_MESSAGE_TYPE_END_ASSESSMENT,
                LtiMessageInterface::LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST,
            ],
            $this->subject->getSupportedMessageTypes()
        );
    }
    public function testValidatePlatformOriginatingLaunchForLtiResourceLinkSuccess(): void
    {
        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
           LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $this->registration->getTool()->getLaunchUrl(),
            'loginHint',
            null,
            [],
            [
                new ResourceLinkClaim('identifier')
            ]
        );

        $result = $this->subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getPlatformKeyChain()->getPublicKey());
        $this->verifyJwt($result->getState()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'ID token kid header is provided',
                'ID token validation success',
                'ID token version claim is valid',
                'ID token message_type claim is valid',
                'ID token roles claim is valid',
                'ID token user identifier (sub) claim is valid',
                'ID token nonce claim is valid',
                'ID token deployment_id claim valid for this registration',
                'ID token message type claim LtiResourceLinkRequest requirements are valid',
                'State validation success',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals('identifier', $result->getPayload()->getResourceLink()->getIdentifier());
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function testValidatePlatformOriginatingLaunchWithoutNonceAndStateValidationsLinkSuccess(): void
    {
        $isStateValidationRequiredProperty = new ReflectionProperty(
            ToolLaunchValidator::class,
            "isStateValidationRequired"
        );
        $isStateValidationRequiredProperty->setAccessible(true);
        $isStateValidationRequiredProperty->setValue($this->subject, false);

        $isNonceValidationRequiredProperty = new ReflectionProperty(
            ToolLaunchValidator::class,
            "isNonceValidationRequired"
        );
        $isNonceValidationRequiredProperty->setAccessible(true);
        $isNonceValidationRequiredProperty->setValue($this->subject, false);

        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $this->registration->getTool()->getLaunchUrl(),
            'loginHint',
            null,
            [],
            [
                new ResourceLinkClaim('identifier')
            ]
        );

        $result = $this->subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getPlatformKeyChain()->getPublicKey());
        $this->verifyJwt($result->getState()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'ID token kid header is provided',
                'ID token validation success',
                'ID token version claim is valid',
                'ID token message_type claim is valid',
                'ID token roles claim is valid',
                'ID token user identifier (sub) claim is valid',
                'ID token deployment_id claim valid for this registration',
                'ID token message type claim LtiResourceLinkRequest requirements are valid',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals('identifier', $result->getPayload()->getResourceLink()->getIdentifier());
    }

    public function testValidatePlatformOriginatingLaunchForDeepLinkingSuccess(): void
    {
        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
            $this->registration->getTool()->getDeepLinkingUrl(),
            'loginHint',
            null,
            [],
            [
                new DeepLinkingSettingsClaim('http://platform.com/return', ['ltiResourceLink'], ['window']),
            ]
        );

        $result = $this->subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getPlatformKeyChain()->getPublicKey());
        $this->verifyJwt($result->getState()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'ID token kid header is provided',
                'ID token validation success',
                'ID token version claim is valid',
                'ID token message_type claim is valid',
                'ID token roles claim is valid',
                'ID token user identifier (sub) claim is valid',
                'ID token nonce claim is valid',
                'ID token deployment_id claim valid for this registration',
                'ID token message type claim LtiDeepLinkingRequest requirements are valid',
                'State validation success',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals(
            'http://platform.com/return',
            $result->getPayload()->getDeepLinkingSettings()->getDeepLinkingReturnUrl()
        );
    }

    public function testValidatePlatformOriginatingLaunchForStartProctoringSuccess(): void
    {
        $validProctoringData = $this->buildJwt([], [], $this->registration->getPlatformKeyChain()->getPrivateKey());

        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
            $this->registration->getTool()->getLaunchUrl(),
            'loginHint',
            null,
            [],
            [
                new ResourceLinkClaim('identifier'),
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL => 'http://tool.com/start',
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $validProctoringData->toString(),
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => '1',
            ]
        );

        $result = $this->subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getPlatformKeyChain()->getPublicKey());
        $this->verifyJwt($result->getState()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'ID token kid header is provided',
                'ID token validation success',
                'ID token version claim is valid',
                'ID token message_type claim is valid',
                'ID token roles claim is valid',
                'ID token user identifier (sub) claim is valid',
                'ID token nonce claim is valid',
                'ID token deployment_id claim valid for this registration',
                'ID token message type claim LtiStartProctoring requirements are valid',
                'State validation success',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals('http://tool.com/start', $result->getPayload()->getProctoringStartAssessmentUrl());
        $this->assertEquals($validProctoringData->toString(), $result->getPayload()->getProctoringSessionData());
        $this->assertEquals('1', $result->getPayload()->getProctoringAttemptNumber());
    }

    public function testValidatePlatformOriginatingLaunchForEndAssessmentSuccess(): void
    {
        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_END_ASSESSMENT,
            $this->registration->getTool()->getLaunchUrl(),
            'loginHint',
            null,
            [],
            [
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => '1',
            ]
        );

        $result = $this->subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getPlatformKeyChain()->getPublicKey());
        $this->verifyJwt($result->getState()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'ID token kid header is provided',
                'ID token validation success',
                'ID token version claim is valid',
                'ID token message_type claim is valid',
                'ID token roles claim is valid',
                'ID token user identifier (sub) claim is valid',
                'ID token nonce claim is valid',
                'ID token deployment_id claim valid for this registration',
                'ID token message type claim LtiEndAssessment requirements are valid',
                'State validation success',
            ],
            $result->getSuccesses()
        );

        $this->assertEquals('1', $result->getPayload()->getProctoringAttemptNumber());
    }

    public function testValidatePlatformOriginatingLaunchFallbackOnJwks(): void
    {
        $registration = $this->createTestRegistrationWithoutPlatformKeyChain();

        $jwksFetcherMock = $this->createMock(JwksFetcherInterface::class);
        $jwksFetcherMock
            ->expects($this->once())
            ->method('fetchKey')
            ->willReturn($this->registration->getPlatformKeyChain()->getPublicKey());

        $subject = new ToolLaunchValidator(
            $this->createTestRegistrationRepository([$registration]),
            $this->nonceRepository,
            $jwksFetcherMock
        );

        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $registration->getTool()->getLaunchUrl(),
            'loginHint',
            null,
            [],
            [
                new ResourceLinkClaim('identifier')
            ]
        );

        $result =$subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());
    }

    public function testValidatePlatformOriginatingLaunchFailureOnMissingToolKey(): void
    {
        $registration = $this->createTestRegistrationWithoutToolKeyChain();

        $subject = new ToolLaunchValidator(
            $this->createTestRegistrationRepository([$registration]),
            $this->nonceRepository
        );

        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $registration->getTool()->getLaunchUrl(),
            'loginHint',
            null,
            [],
            [
                new ResourceLinkClaim('identifier')
            ]
        );

        $result =$subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('State validation failure: tool key chain not configured', $result->getError());
    }

    public function testValidatePlatformOriginatingLaunchFailureWithExpiredPayload(): void
    {
        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $this->registration->getTool()->getLaunchUrl(),
            'loginHint'
        );

        Carbon::setTestNow(Carbon::now()->subSeconds(MessagePayloadInterface::TTL + 1));
        $result = $this->subject->validatePlatformOriginatingLaunch($this->buildOidcFlowRequest($message));
        Carbon::setTestNow();

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('ID token validation failure', $result->getError());
    }

    public function testValidatePlatformOriginatingLaunchFailureWithExpiredState(): void
    {
        $token = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $this->registration->getPlatformKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_ISS => $this->registration->getPlatform()->getAudience(),
                MessagePayloadInterface::CLAIM_AUD => $this->registration->getClientId(),
                LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $this->registration->getDefaultDeploymentId(),
                LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => ['id' => 'identifier'],
            ],
            $this->registration->getPlatformKeyChain()->getPrivateKey()
        );

        Carbon::setTestNow(Carbon::now()->subSeconds(MessagePayloadInterface::TTL + 1));
        $state = $this->buildJwt([], [], $this->registration->getToolKeyChain()->getPrivateKey());
        Carbon::setTestNow();

        $request = $this->createServerRequest('GET', sprintf(
            '%s?id_token=%s&state=%s',
            $this->registration->getTool()->getLaunchUrl(),
            $token->toString(),
            $state->toString()
        ));

        $result = $this->subject->validatePlatformOriginatingLaunch($request);

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('State validation failure', $result->getError());
    }

    /**
     * @dataProvider provideValidationFailureContexts
     */
    public function testValidatePlatformOriginatingLaunchContextualFailures(
        array $tokenHeaders,
        array $tokenClaims,
        string $expectedErrorMessage
    ): void {
        $token = $this->buildJwt(
            $tokenHeaders,
            $tokenClaims,
            $this->registration->getPlatformKeyChain()->getPrivateKey()
        );

        $request = $this->createServerRequest('GET', sprintf(
            '%s?id_token=%s&state=%s',
            $this->registration->getTool()->getLaunchUrl(),
            $token->toString(),
            $this->buildJwt()->toString()
        ));

        $result = $this->subject->validatePlatformOriginatingLaunch($request);

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals($expectedErrorMessage, $result->getError());
    }

    public function provideValidationFailureContexts(): array
    {
        $registration = $this->createTestRegistration();

        return [
            'Invalid registration' => [
                [],
                [
                    MessagePayloadInterface::CLAIM_ISS => 'invalid',
                    MessagePayloadInterface::CLAIM_AUD => 'invalid'
                ],
                'No matching registration found tool side'
            ],
            'Missing ID token kid header' => [
                [],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId()
                ],
                'ID token kid header is missing'
            ],
            'Invalid ID token version' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => ''
                ],
                'ID token version claim is invalid'
            ],
            'Missing ID token message type' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => '',
                ],
                'ID token message_type claim is not supported'
            ],
            'Invalid ID token message type' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => 'invalid',
                ],
                'ID token message_type claim is not supported'
            ],
            'Invalid ID token roles' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => '',
                ],
                'ID token roles claim is invalid'
            ],
            'Invalid ID token user identity' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => '',
                ],
                'ID token user identifier (sub) claim is invalid'
            ],
            'Missing ID token nonce' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => '',
                ],
                'ID token nonce claim is missing'
            ],
            'Invalid ID token nonce' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'existing',
                ],
                'ID token nonce claim already used'
            ],
            'Missing ID token deployment id' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                ],
                'ID token deployment_id claim is missing'
            ],
            'Invalid ID token deployment id' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => 'invalid',
                ],
                'ID token deployment_id claim not valid for this registration'
            ],
            'Invalid ID token for resource launch without resource link' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                ],
                'ID token resource_link claim is missing'
            ],
            'Invalid ID token for resource launch without resource link id' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => ['id' => ''],
                ],
                'ID token resource_link id claim is invalid'
            ],
            'Invalid ID token for deep linking without settings' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId()
                ],
                'ID token deep_linking_settings id claim is invalid'
            ],
            'Invalid ID token for start proctoring without start assessment url' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId()
                ],
                'ID token start_assessment_url proctoring claim is invalid'
            ],
            'Invalid ID token for start proctoring without session data' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL => 'startAssessmentUrl',
                ],
                'ID token session_data proctoring claim is invalid'
            ],
            'Invalid ID token for start proctoring without attempt number' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL => 'startAssessmentUrl',
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => 'sessionData',
                ],
                'ID token attempt_number proctoring claim is invalid'
            ],
            'Missing ID token for start proctoring without resource link' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL => 'startAssessmentUrl',
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => 'sessionData',
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => 1,
                ],
                'ID token resource_link claim is missing'
            ],
            'Invalid ID token for start proctoring without resource link' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL => 'startAssessmentUrl',
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => 'sessionData',
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => 1,
                    LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => ['id' => ''],
                ],
                'ID token resource_link id claim is invalid'
            ],
            'Invalid ID token for end assessment without attempt number' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_END_ASSESSMENT,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                ],
                'ID token attempt_number proctoring claim is invalid'
            ],
            'Missing ID token AGS claim for submission review' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                ],
                'ID token AGS submission review claim is missing'
            ],
            'Invalid ID token AGS line item claim for submission review' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_AGS => [
                        'scope' => [],
                    ],

                ],
                'ID token AGS line item submission review claim is invalid'
            ],
            'Invalid ID token for_user claim for submission review' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getPlatform()->getAudience(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_SUBMISSION_REVIEW_REQUEST,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_SUB => 'user',
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_AGS => [
                        'scope' => [],
                        'lineitem' => 'lineItemUrl'
                    ],

                ],
                'ID token for_user submission review claim is invalid'
            ],
        ];
    }

    private function buildOidcFlowRequest(LtiMessageInterface $message): ServerRequestInterface
    {
        return $this->createServerRequest('GET', $this->performOidcFlow($message)->toUrl());
    }
}
