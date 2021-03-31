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
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiResourceLinkLaunchRequestBuilderTest extends TestCase
{
    use DomainTestingTrait;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new LtiResourceLinkLaunchRequestBuilder();
    }

    public function testBuildLtiResourceLinkLaunchRequestSuccess(): void
    {
        $registration = $this->createTestRegistration();

        $ltiResourceLink = new LtiResourceLink('identifier');

        $result = $this->subject->buildLtiResourceLinkLaunchRequest(
            $ltiResourceLink,
            $registration,
            'loginHint',
            'deploymentIdentifier',
            [
                'role'
            ],
            [
                new ContextClaim('contextIdentifier')
            ]
        );

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $this->assertEquals($registration->getTool()->getOidcInitiationUrl(), $result->getUrl());
        $this->assertEquals($registration->getPlatform()->getAudience(), $result->getParameters()->getMandatory('iss'));
        $this->assertEquals('loginHint', $result->getParameters()->getMandatory('login_hint'));
        $this->assertEquals($registration->getTool()->getLaunchUrl(), $result->getParameters()->getMandatory('target_link_uri'));
        $this->assertEquals('deploymentIdentifier', $result->getParameters()->getMandatory('lti_deployment_id'));
        $this->assertEquals($registration->getClientId(), $result->getParameters()->getMandatory('client_id'));

        $ltiMessageHintToken = $this->parseJwt($result->getParameters()->getMandatory('lti_message_hint'));

        $this->assertTrue($this->verifyJwt($ltiMessageHintToken, $registration->getPlatformKeyChain()->getPublicKey()));

        $this->assertEquals(
            LtiMessageInterface::LTI_VERSION,
            $ltiMessageHintToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_VERSION)
        );
        $this->assertEquals(
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $ltiMessageHintToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE)
        );
        $this->assertEquals(
            ['role'],
            $ltiMessageHintToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_ROLES)
        );
        $this->assertEquals(
            'contextIdentifier',
            $ltiMessageHintToken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT)['id'] ?? null
        );
    }

    public function testBuildLtiResourceLinkLaunchRequestError(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot create LTI resource link launch request');

        $this->subject->buildLtiResourceLinkLaunchRequest(
            new LtiResourceLink('identifier'),
            $this->createTestRegistrationWithoutPlatformKeyChain(),
            'loginHint'
        );
    }

    public function testBuildLtiResourceLinkLaunchRequestErrorWithInvalidLaunchUrls(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Neither resource link url nor tool default url were presented');

        $this->subject->buildLtiResourceLinkLaunchRequest(
            new LtiResourceLink('identifier'),
            $this->createTestRegistrationWithoutToolLaunchUrl(),
            'loginHint'
        );
    }

    public function testBuildLtiResourceLinkLaunchRequestLtiErrorRethrow(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('LTI error');

        $builderMock = $this->createMock(MessagePayloadBuilderInterface::class);
        $builderMock
            ->expects($this->any())
            ->method('withClaim')
            ->willThrowException(new LtiException('LTI error'));

        $subject = new LtiResourceLinkLaunchRequestBuilder($builderMock);

        $subject->buildLtiResourceLinkLaunchRequest(
            new LtiResourceLink('identifier'),
            $this->createTestRegistrationWithoutPlatformKeyChain(),
            'loginHint'
        );
    }
}
