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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Message\Launch\Validator;

use Carbon\Carbon;
use Lcobucci\JWT\Signer\Rsa\Sha384;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\PlatformLaunchValidator;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResult;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
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
        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'http://platform.com/launch'
        );

        $result = $this->subject->validateToolOriginatingLaunch($this->createServerRequest('GET', $message->toUrl()));

        $this->assertInstanceOf(LaunchValidationResult::class, $result);
        $this->assertFalse($result->hasError());

        $this->verifyJwt($result->getPayload()->getToken(), $this->registration->getToolKeyChain()->getPublicKey());

        $this->assertEquals(
            [
                'JWT is not expired',
                'JWT kid header is provided',
                'JWT version claim is valid',
                'JWT message_type claim is valid',
                'JWT signature validation success',
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

        $this->assertInstanceOf(LaunchValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT is expired', $result->getError());
    }

    public function testValidateToolOriginatingLaunchFailureOnInvalidPayloadSignature(): void
    {
        $token = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => $this->registration->getPlatformKeyChain()->getIdentifier()
            ],
            [
                MessagePayloadInterface::CLAIM_ISS => $this->registration->getClientId(),
                MessagePayloadInterface::CLAIM_AUD => $this->registration->getPlatform()->getAudience(),
                LtiMessagePayloadInterface::CLAIM_LTI_VERSION => LtiMessageInterface::LTI_VERSION,
                LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE => LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
                LtiMessagePayloadInterface::CLAIM_LTI_ROLES => ['Learner'],
                LtiMessagePayloadInterface::CLAIM_NONCE => 'value',
                LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID => $this->registration->getDefaultDeploymentId(),
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => 'data',
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => '1',
                LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => ['id' => 'identifier'],
            ],
            $this->registration->getPlatformKeyChain()->getPrivateKey(),
            new Sha384()
        );

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://platform.com/launch?JWT=%s', $token->__toString())
        );

        $result = $this->subject->validateToolOriginatingLaunch($request);

        $this->assertInstanceOf(LaunchValidationResult::class, $result);
        $this->assertTrue($result->hasError());
        $this->assertEquals('JWT signature validation failure', $result->getError());
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
            sprintf('http://platform.com/launch?JWT=%s', $token->__toString())
        );

        $result = $this->subject->validateToolOriginatingLaunch($request);

        $this->assertInstanceOf(LaunchValidationResult::class, $result);
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
                ],
                'JWT session_data proctoring claim is invalid'
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
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => 'data',
                ],
                'JWT attempt_number proctoring claim is invalid'
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
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => 'data',
                    LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => '1',
                    LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => ['id' => ''],
                ],
                'JWT resource_link id claim is invalid'
            ],
        ];
    }
}
