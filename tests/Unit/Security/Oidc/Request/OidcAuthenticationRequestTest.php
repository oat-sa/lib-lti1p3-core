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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Oidc\Request;

use OAT\Library\Lti1p3Core\Security\Oidc\Request\OidcAuthenticationRequest;
use PHPUnit\Framework\TestCase;

class OidcAuthenticationRequestTest extends TestCase
{
    public function testGetters(): void
    {
        $subject = new OidcAuthenticationRequest('http://example.com', [
            'redirect_uri' => 'redirect_uri',
            'client_id' => 'client_id',
            'login_hint' => 'login_hint',
            'nonce' => 'nonce',
            'state' => 'state',
            'lti_message_hint' => 'lti_message_hint'
        ]);

        $this->assertEquals('http://example.com', $subject->getUrl());
        $this->assertEquals('redirect_uri', $subject->getRedirectUri());
        $this->assertEquals('client_id', $subject->getClientId());
        $this->assertEquals('login_hint', $subject->getLoginHint());
        $this->assertEquals('nonce', $subject->getNonce());
        $this->assertEquals('state', $subject->getState());
        $this->assertEquals('lti_message_hint', $subject->getLtiMessageHint());
        $this->assertEquals(OidcAuthenticationRequest::RESPONSE_MODE, $subject->getResponseMode());
        $this->assertEquals(OidcAuthenticationRequest::RESPONSE_TYPE, $subject->getResponseType());
        $this->assertEquals(OidcAuthenticationRequest::SCOPE, $subject->getScope());
        $this->assertEquals(OidcAuthenticationRequest::PROMPT, $subject->getPrompt());
    }
}
