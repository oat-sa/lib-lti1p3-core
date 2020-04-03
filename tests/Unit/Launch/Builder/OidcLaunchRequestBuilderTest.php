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

use OAT\Library\Lti1p3Core\Message\Builder\MessageBuilder;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\OidcLaunchRequest;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;
use Exception;

class OidcLaunchRequestBuilderTest extends TestCase
{
    use DomainTestingTrait;

    /** @var OidcLaunchRequestBuilder */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new OidcLaunchRequestBuilder();
    }

    public function testBuildOidcResourceLinkLtiLaunchRequest(): void
    {
        $resourceLink = $this->createTestResourceLink();
        $registration = $this->createTestRegistration();

        $result = $this->subject->buildResourceLinkOidcLaunchRequest(
            $resourceLink,
            $registration,
            'loginHint',
            $registration->getDefaultDeploymentId(),
            [
                'Learner'
            ],
            [
                new ContextClaim('id'),
                'aaa' => 'bbb'
            ]
        );

        $this->assertInstanceOf(OidcLaunchRequest::class, $result);
        $this->assertEquals($registration->getPlatform()->getAudience(), $result->getIssuer());
        $this->assertEquals('loginHint', $result->getLoginHint());
        $this->assertEquals($resourceLink->getUrl(), $result->getTargetLinkUri());
        $this->assertEquals($registration->getClientId(), $result->getClientId());

        $ltiMessage = new LtiMessage($this->parseJwt($result->getLtiMessageHint()));

        $this->assertEquals($registration->getDefaultDeploymentId(), $ltiMessage->getDeploymentId());
        $this->assertEquals(['Learner'], $ltiMessage->getRoles());
        $this->assertEquals('id', $ltiMessage->getContext()->getId());
        $this->assertEquals('bbb', $ltiMessage->getClaim('aaa'));
    }

    public function testBuildOidcResourceLinkLtiLaunchRequestFailureOnLtiException(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot generate message token: It was not possible to parse your key');

        $invalidKeyChain = $this->createTestKeyChain('invalid', 'invalid', 'invalid', 'invalid');

        $this->subject->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(
                'id',
                'clientId',
                $this->createTestPlatform(),
                $this->createTestTool(),
                ['deploymentIdentifier'],
                $invalidKeyChain,
                $invalidKeyChain
            ),
            'loginHint'
        );
    }

    public function testBuildOidcResourceLinkLtiLaunchRequestFailureOnInvalidDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid deployment id invalid for registration registrationIdentifier');

        $this->subject->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint',
            'invalid'
        );
    }

    public function testBuildOidcResourceLinkLtiLaunchRequestFailureOnMissingDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Mandatory deployment id is missing');

        $this->subject->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(
                'registrationIdentifier',
                'registrationClientId',
                $this->createTestPlatform(),
                $this->createTestTool(),
                []
            ),
            'loginHint'
        );
    }

    public function testBuildOidcResourceLinkLtiLaunchRequestFailureGenericError(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot create LTI launch request: custom error');

        $messageBuilderMock = $this->createMock(MessageBuilder::class);
        $messageBuilderMock
            ->method('withClaim')
            ->willThrowException(new Exception('custom error'));

        $subject = new OidcLaunchRequestBuilder($messageBuilderMock);

        $subject->buildResourceLinkOidcLaunchRequest(
            $this->createTestResourceLink(),
            $this->createTestRegistration(),
            'loginHint'
        );
    }
}
