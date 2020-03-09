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

use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequest;
use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequestParameters;
use PHPUnit\Framework\TestCase;

class AuthenticationRequestTest extends TestCase
{
    public function testItCanBuildUrl(): void
    {
        $authenticationRequest = new AuthenticationRequest(
            'baseUrl',
            new AuthenticationRequestParameters(
                'redirectUri',
                'clientId',
                'loginHint',
                'nonce',
                null,
                'ltiMessageHint'
            )
        );

        $this->assertEquals(
            'baseUrl?scope=openid&response_type=id_token&client_id=clientId&redirect_uri=redirectUri&login_hint=loginHint&response_mode=form_post&nonce=nonce&prompt=none&lti_message_hint=ltiMessageHint',
            $authenticationRequest->buildUrl()
        );
    }

    public function testItCanBuildUrlAndPreserveOidcParameters(): void
    {
        $authenticationRequest = new AuthenticationRequest(
            'baseUrl',
            new AuthenticationRequestParameters(
                'redirectUri',
                'clientId',
                'loginHint',
                'nonce'
            )
        );

        $this->assertEquals(
            'baseUrl?scope=openid&response_type=id_token&response_mode=form_post&prompt=none&client_id=clientId&redirect_uri=redirectUri&login_hint=loginHint&nonce=nonce',
            $authenticationRequest->buildUrl([
                'scope' => 'invalid',
                'response_type' => 'invalid',
                'response_mode' => 'invalid',
                'prompt' => 'invalid',
            ])
        );
    }

    public function testItCanBuildUrlWithSupplementaryParameters(): void
    {
        $authenticationRequest = new AuthenticationRequest(
            'baseUrl',
            new AuthenticationRequestParameters(
                'redirectUri',
                'clientId',
                'loginHint',
                'nonce'
            )
        );

        $this->assertEquals(
            'baseUrl?some=parameter&scope=openid&response_type=id_token&client_id=clientId&redirect_uri=redirectUri&login_hint=loginHint&response_mode=form_post&nonce=nonce&prompt=none',
            $authenticationRequest->buildUrl(['some' => 'parameter'])
        );
    }
}
