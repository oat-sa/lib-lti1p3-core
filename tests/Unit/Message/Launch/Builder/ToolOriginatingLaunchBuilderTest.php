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
use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class ToolOriginatingLaunchBuilderTest extends TestCase
{
    use DomainTestingTrait;

    /** @var ToolOriginatingLaunchBuilder */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new ToolOriginatingLaunchBuilder();
    }

    public function testBuildToolOriginatingLaunchLaunchSuccess(): void
    {
        $registration = $this->createTestRegistration();

        $result = $this->subject->buildToolOriginatingLaunch(
            $registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'platformUrl',
            'deploymentIdentifier',
            [
                'a' => 'b',
                new ContextClaim('contextIdentifier')
            ]
        );

        $this->assertInstanceOf(LtiMessageInterface::class, $result);

        $this->assertEquals('platformUrl', $result->getUrl());

        $token = $this->parseJwt($result->getParameters()->getMandatory('JWT'));

        $this->assertTrue($this->verifyJwt($token, $registration->getToolKeyChain()->getPublicKey()));

        $this->assertEquals('b', $token->getClaims()->get('a'));
        $this->assertEquals(
            'contextIdentifier',
            $token->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT)['id'] ?? null
        );
    }

    public function testBuildToolOriginatingLaunchLaunchErrorOnInvalidDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Invalid deployment id invalid for registration registrationIdentifier');

        $this->subject->buildToolOriginatingLaunch(
            $this->createTestRegistration(),
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'platformUrl',
            'invalid'
        );
    }

    public function testBuildToolOriginatingLaunchLaunchErrorOnMissingDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Mandatory deployment id is missing');

        $this->subject->buildToolOriginatingLaunch(
            $this->createTestRegistrationWithoutDeploymentId(),
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'platformUrl'
        );
    }
}
