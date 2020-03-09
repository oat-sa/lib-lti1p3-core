<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Oidc;

use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequest;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequestParameters;
use PHPUnit\Framework\TestCase;

class LoginInitiationRequestTest extends TestCase
{
    /** @var LoginInitiationRequest */
    private $logintInitiationRequest;
    
    public function setUp()
    {
        $this->logintInitiationRequest = new LoginInitiationRequest(
            'baseUrl',
            new LoginInitiationRequestParameters(
                'issuer',
                'loginHint',
                'targetLinkUri',
                'ltiMessageHint',
                null,
                'clientId'
            )
        );
    }

    public function testItCanBuildUrl(): void
    {
        $this->assertEquals(
            'baseUrl?iss=issuer&login_hint=loginHint&target_link_uri=targetLinkUri&lti_message_hint=ltiMessageHint&client_id=clientId',
            $this->logintInitiationRequest->buildUrl()
        );
    }

    public function testItCanBuildUrlAndPreserveLoginParameters(): void
    {
        $this->assertEquals(
            'baseUrl?iss=issuer&login_hint=loginHint&target_link_uri=targetLinkUri&lti_message_hint=ltiMessageHint&client_id=clientId',
            $this->logintInitiationRequest->buildUrl([
                'iss' => 'invalid',
                'login_hint' => 'invalid',
                'target_link_uri' => 'invalid',
                'lti_message_hint' => 'invalid',
                'client_id' => 'invalid',
            ])
        );
    }

    public function testItCanBuildUrlWithSupplementaryParameters(): void
    {
        $this->assertEquals(
            'baseUrl?some=parameter&iss=issuer&login_hint=loginHint&target_link_uri=targetLinkUri&lti_message_hint=ltiMessageHint&client_id=clientId',
            $this->logintInitiationRequest->buildUrl(['some' => 'parameter'])
        );
    }

    public function testGetBaseUrl(): void
    {
        $this->assertEquals('baseUrl', $this->logintInitiationRequest->getBaseUrl());
    }

    public function testGetParameters(): void
    {
        $loginInitiationRequestParameters = new LoginInitiationRequestParameters(
            'issuer',
            'loginHint',
            'targetLinkUri',
            'ltiMessageHint',
            null,
            'clientId'
        );
        $this->assertEquals($loginInitiationRequestParameters, $this->logintInitiationRequest->getParameters());
    }
}
