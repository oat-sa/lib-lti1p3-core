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
    /** @var OidcAuthenticationRequest */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new OidcAuthenticationRequest('http://example.com', [
            'redirect_uri' => 'redirect_uri',
            'client_id' => 'client_id',
            'login_hint' => 'login_hint',
            'nonce' => 'nonce',
            'state' => 'state',
            'lti_message_hint' => 'lti_message_hint'
        ]);
    }

    public function testGetters(): void
    {
        $this->assertEquals('http://example.com', $this->subject->getUrl());
        $this->assertEquals('redirect_uri', $this->subject->getRedirectUri());
        $this->assertEquals('client_id', $this->subject->getClientId());
        $this->assertEquals('login_hint', $this->subject->getLoginHint());
        $this->assertEquals('nonce', $this->subject->getNonce());
        $this->assertEquals('state', $this->subject->getState());
        $this->assertEquals('lti_message_hint', $this->subject->getLtiMessageHint());
        $this->assertEquals(OidcAuthenticationRequest::RESPONSE_MODE, $this->subject->getResponseMode());
        $this->assertEquals(OidcAuthenticationRequest::RESPONSE_TYPE, $this->subject->getResponseType());
        $this->assertEquals(OidcAuthenticationRequest::SCOPE, $this->subject->getScope());
        $this->assertEquals(OidcAuthenticationRequest::PROMPT, $this->subject->getPrompt());
    }

    public function testGetParameters(): void
    {
        $this->assertEquals(
            [
                'redirect_uri' => 'redirect_uri',
                'client_id' => 'client_id',
                'login_hint' => 'login_hint',
                'nonce' => 'nonce',
                'state' => 'state',
                'lti_message_hint' => 'lti_message_hint',
                'scope' => 'openid',
                'response_type' => 'id_token',
                'response_mode' => 'form_post',
                'prompt' => 'none',
            ],
            $this->subject->getParameters()
        );
    }

    public function testtoUrl(): void
    {
        $this->assertEquals(
            'http://example.com?redirect_uri=redirect_uri&client_id=client_id&login_hint=login_hint&nonce=nonce&state=state&lti_message_hint=lti_message_hint&scope=openid&response_type=id_token&response_mode=form_post&prompt=none',
            $this->subject->toUrl()
        );
    }
}
