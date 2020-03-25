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

use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLinkInterface;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Claim\LisClaim;
use OAT\Library\Lti1p3Core\Message\Claim\PlatformInstanceClaim;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;
use PHPUnit\Framework\TestCase;

class LtiMessageTest extends TestCase
{
    use DomainTestingTrait;
    use SecurityTestingTrait;

    /** @var LtiLaunchRequestBuilder */
    private $builder;

    /** @var ResourceLinkInterface */
    private $resourceLink;

    /** @var DeploymentInterface */
    private $deployment;

    /** @var UserIdentityInterface */
    private $userIdentity;

    /** @var LtiMessage */
    private $subject;

    protected function setUp(): void
    {
        $this->builder = new LtiLaunchRequestBuilder();

        $this->resourceLink = $this->createTestResourceLink();
        $this->deployment = $this->createTestDeployment();
        $this->userIdentity = $this->createTestUserIdentity();

        $message = $this->builder
            ->buildUserResourceLinkLtiLaunchRequest(
                $this->resourceLink,
                $this->deployment,
                $this->userIdentity,
                [
                    'Learner'
                ],
                [
                    LtiMessageInterface::CLAIM_LTI_ROLE_SCOPE_MENTOR => ['mentor'],
                    LtiMessageInterface::CLAIM_LTI_CUSTOM => ['custom'],
                    new ContextClaim('id'),
                    new PlatformInstanceClaim('guid'),
                    new LaunchPresentationClaim('document_target'),
                    new LisClaim('course_offering_sourcedid'),
                    'aaa' => 'bbb'
                ]
            )->getLtiMessage();

        $this->subject = new LtiMessage($this->parseJwt($message));
    }

    public function testGetMessageType(): void
    {
        $this->assertEquals(ResourceLink::TYPE, $this->subject->getMessageType());
    }

    public function testGetVersion(): void
    {
        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $this->subject->getVersion());
    }

    public function testGetDeploymentId(): void
    {
        $this->assertEquals($this->deployment->getIdentifier(), $this->subject->getDeploymentId());
    }

    public function testGetTargetLinkUri(): void
    {
        $this->assertEquals($this->resourceLink->getUrl(), $this->subject->getTargetLinkUri());
    }

    public function testGetResourceLink(): void
    {
        $claim = ResourceLinkClaim::denormalize([
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
        $this->assertInstanceOf(ContextClaim::class, $this->subject->getContext());
        $this->assertEquals('id', $this->subject->getContext()->getId());
    }

    public function testGetPlatformInstance(): void
    {
        $this->assertInstanceOf(PlatformInstanceClaim::class, $this->subject->getPlatformInstance());
        $this->assertEquals('guid', $this->subject->getPlatformInstance()->getGuid());
    }

    public function testGetLaunchPresentation(): void
    {
        $this->assertInstanceOf(LaunchPresentationClaim::class, $this->subject->getLaunchPresentation());
        $this->assertEquals('document_target', $this->subject->getLaunchPresentation()->getDocumentTarget());
    }

    public function testGetLis(): void
    {
        $this->assertInstanceOf(LisClaim::class, $this->subject->getLis());
        $this->assertEquals('course_offering_sourcedid', $this->subject->getLis()->getCourseOfferingSourcedId());
    }
}
