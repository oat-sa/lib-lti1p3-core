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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload;

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AcsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingContentItemsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LisClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\NrpsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\PlatformInstanceClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ProctoringSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiMessagePayloadTest extends TestCase
{
    use DomainTestingTrait;

    /** @var MessagePayloadBuilderInterface */
    private $builder;

    /** @var ContextClaim */
    private $claim;

    /** @var MessagePayloadInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->builder = new MessagePayloadBuilder();
        $this->claim = new ContextClaim('identifier');

        $payload = $this->builder
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_MESSAGE_TYPE, LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_VERSION, LtiMessageInterface::LTI_VERSION)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEPLOYMENT_ID, 'deploymentIdentifier')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_TARGET_LINK_URI, 'targetLinkUri')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLES, ['Learner'])
            ->withClaim(new ResourceLinkClaim('resourceLinkIdentifier'))
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_ROLE_SCOPE_MENTOR, ['mentor'])
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_CUSTOM, ['custom'])
            ->withClaim(new ContextClaim('contextIdentifier'))
            ->withClaim(new PlatformInstanceClaim('platformIdentifier'))
            ->withClaim(new LaunchPresentationClaim('window'))
            ->withClaim(new LisClaim('lisIdentifier'))
            ->withClaim(new DeepLinkingSettingsClaim('deepLinkingReturnUrl', ['ltiResourceLink'], ['window']))
            ->withClaim(new DeepLinkingContentItemsClaim(['item']))
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA, 'deepLinkingData')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_MESSAGE, 'deepLinkingMessage')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_ERROR_MESSAGE, 'deepLinkingErrorMessage')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_LOG, 'deepLinkingLog')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_ERROR_LOG, 'deepLinkingErrorLog')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_START_ASSESSMENT_URL, 'startAssessmentUrl')
            ->withClaim(new ProctoringSettingsClaim('proctoringSettings'))
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA, 'proctoringSessionData')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER, '1')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_VERIFIED_USER, ['picture' => 'picture'])
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_END_ASSESSMENT_RETURN, true)
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ERROR_MESSAGE, 'proctoringErrorMessage')
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ERROR_LOG, 'proctoringErrorLog')
            ->withClaim(new AcsClaim(['action'], 'assessmentControlUrl'))
            ->withClaim(new AgsClaim(['scope'], 'lineItemContainerUrl'))
            ->withClaim(new NrpsClaim('membershipUrl'))
            ->withClaim(new BasicOutcomeClaim('sourcedId', 'serviceUrl'))
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());

        $this->subject = new LtiMessagePayload($this->parseJwt($payload->getToken()->toString()));
    }

    public function testClaims(): void
    {
        $this->assertEquals(LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST, $this->subject->getMessageType());
        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $this->subject->getVersion());
        $this->assertEquals('deploymentIdentifier', $this->subject->getDeploymentId());
        $this->assertEquals('targetLinkUri', $this->subject->getTargetLinkUri());
        $this->assertEquals(['Learner'], $this->subject->getRoles());
        $this->assertEquals('resourceLinkIdentifier', $this->subject->getResourceLink()->getIdentifier());
        $this->assertEquals(['mentor'], $this->subject->getRoleScopeMentor());
        $this->assertEquals(['custom'], $this->subject->getCustom());
        $this->assertEquals('contextIdentifier', $this->subject->getContext()->getIdentifier());
        $this->assertEquals('platformIdentifier', $this->subject->getPlatformInstance()->getGuid());
        $this->assertEquals('window', $this->subject->getLaunchPresentation()->getDocumentTarget());
        $this->assertEquals('lisIdentifier', $this->subject->getLis()->getCourseOfferingSourcedId());
        $this->assertEquals('deepLinkingReturnUrl', $this->subject->getDeepLinkingSettings()->getDeepLinkingReturnUrl());
        $this->assertEquals(['item'], $this->subject->getDeepLinkingContentItems()->getContentItems());
        $this->assertEquals('deepLinkingData', $this->subject->getDeepLinkingData());
        $this->assertEquals('deepLinkingMessage', $this->subject->getDeepLinkingMessage());
        $this->assertEquals('deepLinkingErrorMessage', $this->subject->getDeepLinkingErrorMessage());
        $this->assertEquals('deepLinkingLog', $this->subject->getDeepLinkingLog());
        $this->assertEquals('deepLinkingErrorLog', $this->subject->getDeepLinkingErrorLog());
        $this->assertEquals('startAssessmentUrl', $this->subject->getProctoringStartAssessmentUrl());
        $this->assertEquals('proctoringSettings', $this->subject->getProctoringSettings()->getData());
        $this->assertEquals('proctoringSessionData', $this->subject->getProctoringSessionData());
        $this->assertTrue($this->subject->getProctoringEndAssessmentReturn());
        $this->assertEquals('proctoringErrorMessage', $this->subject->getProctoringErrorMessage());
        $this->assertEquals('proctoringErrorLog', $this->subject->getProctoringErrorLog());
        $this->assertEquals('1', $this->subject->getProctoringAttemptNumber());
        $this->assertEquals(['picture' => 'picture'], $this->subject->getProctoringVerifiedUser()->getUserData());
        $this->assertEquals(['action'], $this->subject->getAcs()->getActions());
        $this->assertEquals(['scope'], $this->subject->getAgs()->getScopes());
        $this->assertEquals('membershipUrl', $this->subject->getNrps()->getContextMembershipsUrl());
        $this->assertEquals('sourcedId', $this->subject->getBasicOutcome()->getLisResultSourcedId());
        $this->assertNull($this->subject->getUserIdentity());
    }

    public function testGetUserIdentity(): void
    {
        $payload = $this->builder
            ->reset()
            ->withClaim(LtiMessagePayloadInterface::CLAIM_SUB, 'userIdentifier')
            ->withClaim('name', 'userName')
            ->withClaim('email', 'user@example.com')
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());

        $this->subject = new LtiMessagePayload($this->parseJwt($payload->getToken()->toString()));

        $this->assertEquals('userIdentifier', $this->subject->getUserIdentity()->getIdentifier());
        $this->assertEquals('userName', $this->subject->getUserIdentity()->getName());
        $this->assertEquals('user@example.com', $this->subject->getUserIdentity()->getEmail());
    }

    public function testGetValidatedRoleCollection(): void
    {
        $payload = $this->builder
            ->reset()
            ->withClaim(
                LtiMessagePayloadInterface::CLAIM_LTI_ROLES,
                [
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                    'Learner'
                ]
            )
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());

        $this->subject = new LtiMessagePayload($this->parseJwt($payload->getToken()->toString()));

        $this->assertEquals(3, $this->subject->getValidatedRoleCollection()->count());
    }

    public function testGetEndAssessmentReturnDefaultValue(): void
    {
        $payload = $this->builder
            ->reset()
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());

        $this->subject = new LtiMessagePayload($this->parseJwt($payload->getToken()->toString()));

        $this->assertFalse($this->subject->getProctoringEndAssessmentReturn());
    }

    public function testGetEndAssessmentReturnFalseValue(): void
    {
        $payload = $this->builder
            ->reset()
            ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_END_ASSESSMENT_RETURN, false)
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());

        $this->subject = new LtiMessagePayload($this->parseJwt($payload->getToken()->toString()));

        $this->assertFalse($this->subject->getProctoringEndAssessmentReturn());
    }
}
