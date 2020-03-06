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

use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequestParameters;
use PHPUnit\Framework\TestCase;

class AuthenticationRequestParametersTest extends TestCase
{
    private $authenticationRequestParameters;
    
    public function setUp(): void
    {
        $this->authenticationRequestParameters = new AuthenticationRequestParameters(
            'redirectUri',
            'clientId',
            'loginHint',
            'nonce',
            'state',
            'ltiMessageHint'
        );
    }

    public function testGetRedirectUri(): void
    {
        $this->assertEquals('redirectUri', $this->authenticationRequestParameters->getRedirectUri());
    }

    public function testGetClientId(): void
    {
        $this->assertEquals('clientId', $this->authenticationRequestParameters->getClientId());
    }

    public function testGetLoginHint(): void
    {
        $this->assertEquals('loginHint', $this->authenticationRequestParameters->getLoginHint());
    }

    public function testGetNonce(): void
    {
        $this->assertEquals('nonce', $this->authenticationRequestParameters->getNonce());
    }

    public function testGetState(): void
    {
        $this->assertEquals('state', $this->authenticationRequestParameters->getState());
    }

    public function testGetLtiMessageHint(): void
    {
        $this->assertEquals('ltiMessageHint', $this->authenticationRequestParameters->getLtiMessageHint());
    }

    public function testGetScope(): void
    {
        $this->assertEquals(
            AuthenticationRequestParameters::SCOPE,
            $this->authenticationRequestParameters->getScope()
        );
    }

    public function testGetResponseType(): void
    {
        $this->assertEquals(
            AuthenticationRequestParameters::RESPONSE_TYPE,
            $this->authenticationRequestParameters->getResponseType()
        );
    }

    public function testGetResponseMode(): void
    {
        $this->assertEquals(
            AuthenticationRequestParameters::RESPONSE_MODE,
            $this->authenticationRequestParameters->getResponseMode()
        );
    }

    public function testGetPrompt(): void
    {
        $this->assertEquals(
            AuthenticationRequestParameters::PROMPT,
            $this->authenticationRequestParameters->getPrompt()
        );
    }
}
