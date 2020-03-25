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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\Oidc\Endpoint;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginInitiator;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class OidcLoginAuthenticatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    public function testAuthenticationSuccess(): void
    {
        $deploymentRepository = $this->createTestDeploymentRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($deploymentRepository);
        $subject = new OidcLoginAuthenticator($deploymentRepository, $this->createTestUserAuthenticator());

        $resourceLink = $this->createTestResourceLink();
        $deployment = $this->createTestDeployment();

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $resourceLink,
            $deployment,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $result = $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $idToken = $this->parseJwt($result->getLtiMessage());

        $this->assertInstanceOf(LtiLaunchRequest::class, $result);

        $this->assertEquals($resourceLink->getUrl(), $result->getUrl());

        $this->assertTrue($this->verifyJwt($idToken, $deployment->getPlatformKeyChain()->getPublicKey()));

        $this->assertTrue($this->verifyJwt(
            $this->parseJwt($result->getOidcState()),
            $deployment->getToolKeyChain()->getPublicKey()
        ));

        $ltiMessage = new LtiMessage($idToken);
        $this->assertEquals($this->createTestUserIdentity(), $ltiMessage->getUserIdentity());
    }

    public function testAnonymousAuthenticationSuccess(): void
    {
        $deploymentRepository = $this->createTestDeploymentRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($deploymentRepository);
        $subject = new OidcLoginAuthenticator($deploymentRepository, $this->createTestUserAuthenticator(true, true));

        $resourceLink = $this->createTestResourceLink();
        $deployment = $this->createTestDeployment();

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $resourceLink,
            $deployment,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $result = $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );

        $idToken = $this->parseJwt($result->getLtiMessage());

        $this->assertInstanceOf(LtiLaunchRequest::class, $result);

        $this->assertEquals($resourceLink->getUrl(), $result->getUrl());

        $this->assertTrue($this->verifyJwt($idToken, $deployment->getPlatformKeyChain()->getPublicKey()));

        $this->assertTrue($this->verifyJwt(
            $this->parseJwt($result->getOidcState()),
            $deployment->getToolKeyChain()->getPublicKey()
        ));

        $ltiMessage = new LtiMessage($idToken);
        $this->assertNull($ltiMessage->getUserIdentity());
    }

    public function testAuthenticationFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('User authentication failure');

        $deploymentRepository = $this->createTestDeploymentRepository();
        $oidcLoginInitiator = new OidcLoginInitiator($deploymentRepository);
        $subject = new OidcLoginAuthenticator($deploymentRepository, $this->createTestUserAuthenticator(false));

        $resourceLink = $this->createTestResourceLink();
        $deployment = $this->createTestDeployment();

        $oidcLaunchRequest = (new OidcLaunchRequestBuilder())->buildResourceLinkOidcLaunchRequest(
            $resourceLink,
            $deployment,
            'loginHint'
        );

        $oidcAuthRequest = $oidcLoginInitiator->initiate(
            $this->createServerRequest('GET', $oidcLaunchRequest->toUrl())
        );

        $subject->authenticate(
            $this->createServerRequest('POST', $oidcAuthRequest->getUrl(), $oidcAuthRequest->getParameters())
        );
    }
}
