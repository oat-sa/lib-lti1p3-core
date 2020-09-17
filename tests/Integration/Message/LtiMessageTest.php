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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Message;

use OAT\Library\Lti1p3Core\Token\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Token\Claim\BasicOutcomeTokenClaim;
use OAT\Library\Lti1p3Core\Token\Claim\NrpsTokenClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use OAT\Library\Lti1p3Core\Token\Claim\ContextTokenClaim;
use OAT\Library\Lti1p3Core\Token\Claim\LaunchPresentationTokenClaim;
use OAT\Library\Lti1p3Core\Token\Claim\LisTokenClaim;
use OAT\Library\Lti1p3Core\Token\Claim\PlatformInstanceTokenClaim;
use OAT\Library\Lti1p3Core\Token\Claim\ResourceLinkTokenClaim;
use OAT\Library\Lti1p3Core\Token\LtiMessageToken;
use OAT\Library\Lti1p3Core\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;
use PHPUnit\Framework\TestCase;

class LtiMessageTest extends TestCase
{
    use DomainTestingTrait;

    /** @var LtiLaunchRequestBuilder */
    private $builder;

    /** @var ResourceLinkInterface */
    private $resourceLink;

    /** @var RegistrationInterface */
    private $registration;

    /** @var UserIdentityInterface */
    private $userIdentity;

    /** @var LtiMessageToken */
    private $subject;

    protected function setUp(): void
    {
        $this->builder = new LtiLaunchRequestBuilder();

        $this->resourceLink = $this->createTestResourceLink();
        $this->registration = $this->createTestRegistration();
        $this->userIdentity = $this->createTestUserIdentity();

        $message = $this->builder
            ->buildUserResourceLinkLtiLaunchRequest(
                $this->resourceLink,
                $this->registration,
                $this->userIdentity,
                $this->registration->getDefaultDeploymentId(),
                [
                    'Learner'
                ],
                [
                    LtiMessageTokenInterface::CLAIM_LTI_ROLE_SCOPE_MENTOR => ['mentor'],
                    LtiMessageTokenInterface::CLAIM_LTI_CUSTOM => ['custom'],
                    new ContextTokenClaim('id'),
                    new PlatformInstanceTokenClaim('guid'),
                    new LaunchPresentationTokenClaim('document_target'),
                    new LisTokenClaim('course_offering_sourcedid'),
                    new AgsClaim(['scope'], 'line_items_container_url'),
                    new NrpsTokenClaim('context_membership_url', ['1.0', '2.0']),
                    new BasicOutcomeTokenClaim('id', 'url'),
                    'aaa' => 'bbb'
                ]
            )->getLtiMessage();

        $this->subject = new LtiMessageToken($this->parseJwt($message));
    }

    public function testGetMessageType(): void
    {
        $this->assertEquals(LtiResourceLink::TYPE, $this->subject->getMessageType());
    }

    public function testGetVersion(): void
    {
        $this->assertEquals(LtiMessageTokenInterface::LTI_VERSION, $this->subject->getVersion());
    }

    public function testGetDeploymentId(): void
    {
        $this->assertEquals($this->registration->getDefaultDeploymentId(), $this->subject->getDeploymentId());
    }

    public function testGetTargetLinkUri(): void
    {
        $this->assertEquals($this->resourceLink->getUrl(), $this->subject->getTargetLinkUri());
    }

    public function testGetResourceLink(): void
    {
        $claim = ResourceLinkTokenClaim::denormalize([
            'id' => $this->resourceLink->getIdentifier(),
            'title' => $this->resourceLink->getTitle(),
            'description' => $this->resourceLink->getDescription()
        ]);

        $this->assertEquals($claim, $this->subject->getResourceLink());
    }

    public function testGetRoles(): void
    {
        $this->assertEquals(['Learner'], $this->subject->getRoles());
    }

    public function testGetRoleScopeMentor(): void
    {
        $this->assertEquals(['mentor'], $this->subject->getRoleScopeMentor());
    }

    public function testGetCustom(): void
    {
        $this->assertEquals(['custom'], $this->subject->getCustom());
    }

    public function testGetContext(): void
    {
        $this->assertInstanceOf(ContextTokenClaim::class, $this->subject->getContext());
        $this->assertEquals('id', $this->subject->getContext()->getId());
    }

    public function testGetPlatformInstance(): void
    {
        $this->assertInstanceOf(PlatformInstanceTokenClaim::class, $this->subject->getPlatformInstance());
        $this->assertEquals('guid', $this->subject->getPlatformInstance()->getGuid());
    }

    public function testGetLaunchPresentation(): void
    {
        $this->assertInstanceOf(LaunchPresentationTokenClaim::class, $this->subject->getLaunchPresentation());
        $this->assertEquals('document_target', $this->subject->getLaunchPresentation()->getDocumentTarget());
    }

    public function testGetLis(): void
    {
        $this->assertInstanceOf(LisTokenClaim::class, $this->subject->getLis());
        $this->assertEquals('course_offering_sourcedid', $this->subject->getLis()->getCourseOfferingSourcedId());
    }

    public function testGetAgs(): void
    {
        $this->assertInstanceOf(AgsClaim::class, $this->subject->getAgs());
        $this->assertEquals(['scope'], $this->subject->getAgs()->getScopes());
        $this->assertEquals('line_items_container_url', $this->subject->getAgs()->getLineItemsContainerUrl());
    }

    public function testGetNrps(): void
    {
        $this->assertInstanceOf(NrpsTokenClaim::class, $this->subject->getNrps());
        $this->assertEquals('context_membership_url', $this->subject->getNrps()->getContextMembershipsUrl());
        $this->assertEquals(['1.0', '2.0'], $this->subject->getNrps()->getServiceVersions());
    }

    public function testGetBasicOutcome(): void
    {
        $this->assertInstanceOf(BasicOutcomeTokenClaim::class, $this->subject->getBasicOutcome());
        $this->assertEquals('id', $this->subject->getBasicOutcome()->getLisResultSourcedId());
        $this->assertEquals('url', $this->subject->getBasicOutcome()->getLisOutcomeServiceUrl());
    }
}
