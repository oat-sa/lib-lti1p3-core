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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Launch\Builder;

use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiLaunchRequestBuilderTest extends TestCase
{
    use DomainTestingTrait;
    use SecurityTestingTrait;

    /** @var LtiLaunchRequestBuilder */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LtiLaunchRequestBuilder();
    }

    public function testBuildUserResourceLinkLtiLaunchRequest(): void
    {
        $result = $this->subject->buildUserResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestDeployment(),
            $this->createTestUserIdentity(),
            [
                'Learner'
            ],
            [
                new ContextClaim('id'),
                'aaa' => 'bbb'
            ],
            'state'
        );

        $this->assertInstanceOf(LtiLaunchRequest::class, $result);
        $this->assertEquals('state', $result->getOidcState());

        $ltiMessage = new LtiMessage($this->parseJwt($result->getLtiMessage()));

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $ltiMessage->getVersion());
        $this->assertEquals(['Learner'], $ltiMessage->getRoles());
        $this->assertEquals('id', $ltiMessage->getContext()->getId());
        $this->assertEquals('bbb', $ltiMessage->getClaim('aaa'));
    }

    public function testBuildResourceLinkLtiLaunchRequestFailureOnLtiException(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot generate message token: It was not possible to parse your key');

        $invalidKeyChain = $this->createTestKeyChain('invalid', 'invalid', 'invalid', 'invalid');

        $this->subject->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestDeployment(
                'id',
                'clientId',
                $this->createTestPlatform(),
                $this->createTestTool(),
                $invalidKeyChain,
                $invalidKeyChain
            ),
        );
    }

    public function testBuildResourceLinkLtiLaunchRequestGenericFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot create LTI launch request');

        $this->subject->buildResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $this->createMock(DeploymentInterface::class)
        );
    }
}
