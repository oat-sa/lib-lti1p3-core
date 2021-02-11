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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Launch\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class PlatformOriginatingLaunchBuilderTest extends TestCase
{
    use DomainTestingTrait;

    /** @var PlatformOriginatingLaunchBuilder */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new PlatformOriginatingLaunchBuilder();
    }

    public function testBuildPlatformOriginatingLaunchSuccess(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->buildPlatformOriginatingLaunch(
            $registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'targetLinkUri',
            'loginHint',
            'deploymentIdentifier',
            [
                'role'
            ],
            [
                'a' => 'b',
                new ContextClaim('contextIdentifier')
            ]
        );

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $this->assertEquals($registration->getTool()->getOidcInitiationUrl(), $result->getUrl());
        $this->assertEquals($registration->getPlatform()->getAudience(), $result->getParameters()->getMandatory('iss'));
        $this->assertEquals('loginHint', $result->getParameters()->getMandatory('login_hint'));
        $this->assertEquals('targetLinkUri', $result->getParameters()->getMandatory('target_link_uri'));
        $this->assertEquals('deploymentIdentifier', $result->getParameters()->getMandatory('lti_deployment_id'));
        $this->assertEquals($registration->getClientId(), $result->getParameters()->getMandatory('client_id'));

        $ltiMessageHintToken = $this->parseJwt($result->getParameters()->getMandatory('lti_message_hint'));

        $this->assertTrue($this->verifyJwt($ltiMessageHintToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals(
            ['role'],
            $ltiMessageHintToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_ROLES)
        );
        $this->assertEquals('b', $ltiMessageHintToken->getClaims()->get('a'));
        $this->assertEquals(
            'contextIdentifier',
            $ltiMessageHintToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT)['id'] ?? null
        );
    }

    public function testBuildPlatformOriginatingLaunchSuccessWithUserIdentitySanitization(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->buildPlatformOriginatingLaunch(
            $registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'targetLinkUri',
            'loginHint',
            'deploymentIdentifier',
            [],
            [
                'a' => 'b',
                LtiMessagePayloadInterface::CLAIM_SUB => 'sub',
                LtiMessagePayloadInterface::CLAIM_USER_NAME => 'name',
                LtiMessagePayloadInterface::CLAIM_USER_EMAIL => 'email',
                LtiMessagePayloadInterface::CLAIM_USER_GIVEN_NAME => 'given_name',
                LtiMessagePayloadInterface::CLAIM_USER_FAMILY_NAME => 'family_name',
                LtiMessagePayloadInterface::CLAIM_USER_MIDDLE_NAME => 'middle_name',
                LtiMessagePayloadInterface::CLAIM_USER_LOCALE => 'locale',
                LtiMessagePayloadInterface::CLAIM_USER_PICTURE => 'picture',
            ]
        );

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $ltiMessageHintToken = $this->parseJwt($result->getParameters()->getMandatory('lti_message_hint'));

        $this->assertTrue($this->verifyJwt($ltiMessageHintToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals('b', $ltiMessageHintToken->getClaims()->get('a'));

        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_SUB));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_NAME));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_EMAIL));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_GIVEN_NAME));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_FAMILY_NAME));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_MIDDLE_NAME));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_LOCALE));
        $this->assertFalse($ltiMessageHintToken->getClaims()->has(LtiMessagePayloadInterface::CLAIM_USER_PICTURE));
    }

    public function testBuildPlatformOriginatingLaunchErrorOnInvalidDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid deployment id invalid for registration registrationIdentifier');

        $this->subject->buildPlatformOriginatingLaunch(
            $this->createTestRegistration(),
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'targetLinkUri',
            'loginHint',
            'invalid'
        );
    }

    public function testBuildPlatformOriginatingLaunchErrorOnMissingDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Mandatory deployment id is missing');

        $this->subject->buildPlatformOriginatingLaunch(
            $this->createTestRegistrationWithoutDeploymentId(),
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'targetLinkUri',
            'loginHint'
        );
    }
}
