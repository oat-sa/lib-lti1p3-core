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
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
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
        $this->assertEquals($registration->getPlatform()->getAudience(), $result->getMandatoryParameter('iss'));
        $this->assertEquals('loginHint', $result->getMandatoryParameter('login_hint'));
        $this->assertEquals('targetLinkUri', $result->getMandatoryParameter('target_link_uri'));
        $this->assertEquals('deploymentIdentifier', $result->getMandatoryParameter('lti_deployment_id'));
        $this->assertEquals($registration->getClientId(), $result->getMandatoryParameter('client_id'));

        $ltiMessageHintToken = $this->parseJwt($result->getMandatoryParameter('lti_message_hint'));

        $this->assertTrue($this->verifyJwt($ltiMessageHintToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals(
            ['role'],
            $ltiMessageHintToken->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLES)
        );
        $this->assertEquals('b', $ltiMessageHintToken->getClaim('a'));
        $this->assertEquals(
            'contextIdentifier',
            $ltiMessageHintToken->getClaim(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT)['id'] ?? null
        );
    }

    public function testBuildPlatformOriginatingLaunchSuccessWithUserIdentityClaimsExclusion(): void
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
                'sub' => 'sub',
                'name' => 'name',
                'email' => 'email',
                'given_name' => 'given_name',
                'family_name' => 'family_name',
                'middle_name' => 'middle_name',
                'locale' => 'locale',
                'picture' => 'picture',
            ]
        );

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $ltiMessageHintToken = $this->parseJwt($result->getMandatoryParameter('lti_message_hint'));

        $this->assertTrue($this->verifyJwt($ltiMessageHintToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals('b', $ltiMessageHintToken->getClaim('a'));

        $this->assertFalse($ltiMessageHintToken->hasClaim('sub'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('name'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('email'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('given_name'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('family_name'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('middle_name'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('locale'));
        $this->assertFalse($ltiMessageHintToken->hasClaim('picture'));
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
