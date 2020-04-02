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

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiLaunchRequestBuilderTest extends TestCase
{
    use DomainTestingTrait;

    /** @var LtiLaunchRequestBuilder */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LtiLaunchRequestBuilder();
    }

    public function testBuildUserResourceLinkLtiLaunchRequest(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->buildUserResourceLinkLtiLaunchRequest(
            $this->createTestResourceLink(),
            $registration,
            $this->createTestUserIdentity(),
            $registration->getDefaultDeploymentId(),
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
        $this->assertEquals( $registration->getDefaultDeploymentId(), $ltiMessage->getDeploymentId());
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
            $this->createTestRegistration(
                'id',
                'clientId',
                $this->createTestPlatform(),
                $this->createTestTool(),
                ['deploymentId'],
                $invalidKeyChain,
                $invalidKeyChain
            )
        );
    }
}
