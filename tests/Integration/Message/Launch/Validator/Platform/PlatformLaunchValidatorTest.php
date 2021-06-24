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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Message\Launch\Validator\Platform;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidator;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResult;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResultInterface;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class PlatformLaunchValidatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var NonceRepositoryInterface */
    private $nonceRepository;

    /** @var RegistrationInterface */
    private $registration;

    /** @var ToolOriginatingLaunchBuilder */
    private $builder;

    /** @var PlatformLaunchValidator */
    private $subject;

    protected function setUp(): void
    {
        $this->registrationRepository = $this->createTestRegistrationRepository();
        $this->nonceRepository = $this->createTestNonceRepository();

        $this->registration = $this->createTestRegistration();

        $this->builder = new ToolOriginatingLaunchBuilder();

        $this->subject = new PlatformLaunchValidator($this->registrationRepository, $this->nonceRepository);
    }

    public function testGetSupportedMessageTypes(): void
    {
        $this->assertEquals(
            [
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
            ],
            $this->subject->getSupportedMessageTypes()
        );
    }

    public function testValidateToolOriginatingLaunchSuccess(): void
    {
        $dataToken = $this
            ->buildJwt([], [], $this->registration->getPlatformKeyChain()->getPrivateKey())
            ->toString();

        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'http://platform.com/launch',
            null,
            [
                LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => $dataToken
            ]
        );

        $result = $this->subject->validateToolOriginatingLaunch($this->createServerRequest('GET', $message->toUrl()));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'JWT kid header is provided',
                'JWT validation success',
                'JWT version claim is valid',
                'JWT message_type claim is valid',
                'JWT nonce claim is valid',
                'JWT deployment_id claim valid for this registration',
                'JWT message type claim LtiDeepLinkingResponse requirements are valid',
            ],
            $result->getSuccesses()
        );
    }

    public function testValidateToolOriginatingLaunchFailureOnExpiredPayload(): void
    {
        Carbon::setTestNow(Carbon::now()->subSeconds(MessagePayloadInterface::TTL + 1));
        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'http://platform.com/launch'
        );
        Carbon::setTestNow();

        $result = $this->subject->validateToolOriginatingLaunch($this->createServerRequest('GET', $message->toUrl()));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT validation failure', $result->getError());
    }

    public function testValidateToolOriginatingLaunchFallbackOnJwks(): void
    {
        $registration = $this->createTestRegistrationWithoutToolKeyChain();

        $dataToken = $this
            ->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey())
            ->toString();

        $jwksFetcherMock = $this->createMock(JwksFetcherInterface::class);
        $jwksFetcherMock
            ->expects($this->once())
            ->method('fetchKey')
            ->willReturn($this->registration->getPlatformKeyChain()->getPublicKey());

        $subject = new PlatformLaunchValidator(
            $this->createTestRegistrationRepository([$registration]),
            $this->nonceRepository,
            $jwksFetcherMock
        );

        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'http://platform.com/launch',
            null,
            [
                LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => $dataToken
            ]
        );

        $result = $subject->validateToolOriginatingLaunch($this->createServerRequest('GET', $message->toUrl()));

        $this->assertInstanceOf(LaunchValidationResult::class, $result);
        $this->assertFalse($result->hasError());
    }

    /**
     * @dataProvider provideValidationFailureContexts
     */
    public function testValidateToolOriginatingLaunchContextualFailures(
        array $tokenHeaders,
        array $tokenClaims,
        string $expectedErrorMessage
    ): void {
        $token = $this->buildJwt(
            $tokenHeaders,
            $tokenClaims,
            $this->registration->getPlatformKeyChain()->getPrivateKey()
        );

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/launch?JWT=%s', $token->toString())
        );

        $result = $this->subject->validateToolOriginatingLaunch($request);

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals($expectedErrorMessage, $result->getError());
    }

    public function testValidateToolOriginatingDeepLinkingResponseLaunchFailureOnMissingPlatformKeyChain(): void
    {
        $registration = $this->createTestRegistrationWithoutPlatformKeyChain();

        $repository = $this->createTestRegistrationRepository([$registration]);

        $subject = new PlatformLaunchValidator($repository, $this->nonceRepository);

        $dataToken = $this
            ->buildJwt([], [], $this->registration->getPlatformKeyChain()->getPrivateKey())
            ->toString();

        $message = $this->builder->buildToolOriginatingLaunch(
            $registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'http://platform.com/launch',
            null,
            [
                LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => $dataToken
            ]
        );

        $result = $subject->validateToolOriginatingLaunch($this->createServerRequest('GET', $message->toUrl()));

        $this->assertInstanceOf(LaunchValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals(
            'JWT data deep linking claim validation failure: platform key chain is not configured',
            $result->getError()
        );
    }

    public function testValidateToolOriginatingStartAssessmentLaunchFailureOnMissingPlatformKeyChain(): void
    {
        $registration = $this->createTestRegistrationWithoutPlatformKeyChain();

        $repository = $this->createTestRegistrationRepository([$registration]);

        $subject = new PlatformLaunchValidator($repository, $this->nonceRepository);

        $dataToken = $this
            ->buildJwt([], [], $this->registration->getPlatformKeyChain()->getPrivateKey())
            ->toString();

        $message = $this->builder->buildToolOriginatingLaunch(
            $registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
            'http://platform.com/launch',
            null,
            [
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $dataToken
            ]
        );

        $result = $subject->validateToolOriginatingLaunch($this->createServerRequest('GET', $message->toUrl()));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals(
            'JWT session_data proctoring claim validation failure: platform key chain is not configured',
            $result->getError()
        );
    }

    public function provideValidationFailureContexts(): array
    {
        $registration = $this->createTestRegistration();

        $validProctoringData = $this->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey());

        Carbon::setTestNow(Carbon::now()->subSeconds(MessagePayloadInterface::TTL + 1));
        $invalidDeepLinkingData = $this->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey());
        $invalidProctoringData = $this->buildJwt([], [], $registration->getPlatformKeyChain()->getPrivateKey());
        Carbon::setTestNow();

        return [
            'Invalid registration' => [
                [],
                [
                    MessagePayloadInterface::CLAIM_ISS => 'invalid',
                    MessagePayloadInterface::CLAIM_AUD => 'invalid'
                ],
                'No matching registration found platform side'
            ],
            'Missing JWT kid header' => [
                [],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                ],
                'JWT kid header is missing'
            ],
            'Invalid JWT version' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => ''
                ],
                'JWT version claim is invalid'
            ],
            'Missing JWT message type' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => '',
                ],
                'JWT message_type claim is not supported'
            ],
            'Invalid JWT message type' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => 'invalid',
                ],
                'JWT message_type claim is not supported'
            ],
            'Missing JWT nonce' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => '',
                ],
                'JWT nonce claim is missing'
            ],
            'Invalid JWT nonce' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getToolKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'existing',
                ],
                'JWT nonce claim already used'
            ],
            'Missing JWT deployment id' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                ],
                'JWT deployment_id claim is missing'
            ],
            'Invalid JWT deployment id' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => 'invalid',
                ],
                'JWT deployment_id claim not valid for this registration'
            ],
            'Missing JWT proctoring session data for start assessment' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                ],
                'JWT session_data proctoring claim is missing'
            ],
            'Invalid JWT proctoring session data for start assessment' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $invalidProctoringData->toString(),
                ],
                'JWT session_data proctoring claim validation failure'
            ],
            'Invalid JWT proctoring attempt number for start assessment' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $validProctoringData->toString(),
                ],
                'JWT attempt_number proctoring claim is invalid'
            ],
            'Missing JWT resource link for start assessment' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $validProctoringData->toString(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => '1'
                ],
                'JWT resource_link claim is missing'
            ],
            'Invalid JWT resource link for start assessment' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $validProctoringData->toString(),
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => '1',
                    LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => ['id' => ''],
                ],
                'JWT resource_link id claim is invalid'
            ],
            'Missing JWT data for deep linking response' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => ''
                ],
                'JWT data deep linking claim is missing'
            ],
            'Invalid JWT data for deep linking response' => [
                [
                    MessagePayloadInterface::HEADER_KID => $registration->getPlatformKeyChain()->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => $registration->getPlatform()->getAudience(),
                    LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                    LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                    LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                    LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                    LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $registration->getDefaultDeploymentId(),
                    LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => $invalidDeepLinkingData->toString()
                ],
                'JWT data deep linking claim validation failure'
            ]
        ];
    }
}
