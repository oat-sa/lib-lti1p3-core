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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Launch\Request;

use OAT\Library\Lti1p3Core\Launch\Request\OidcMessage;
use PHPUnit\Framework\TestCase;

class OidcLaunchRequestTest extends TestCase
{
    /** @var OidcMessage */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new OidcMessage('http://example.com', [
            'iss' => 'iss',
            'login_hint' => 'login_hint',
            'target_link_uri' => 'target_link_uri',
            'lti_message_hint' => 'lti_message_hint',
            'lti_deployment_id' => 'lti_deployment_id',
            'client_id' => 'client_id',
        ]);
    }

    public function testGetIssuer(): void
    {
        $this->assertEquals('iss', $this->subject->getIssuer());
    }

    public function testGetLoginHint(): void
    {
        $this->assertEquals('login_hint', $this->subject->getLoginHint());
    }

    public function testGetTargetLinkUri(): void
    {
        $this->assertEquals('target_link_uri', $this->subject->getTargetLinkUri());
    }

    public function testGetLtiMessageHint(): void
    {
        $this->assertEquals('lti_message_hint', $this->subject->getLtiMessageHint());
    }

    public function testGetLtiDeploymentId(): void
    {
        $this->assertEquals('lti_deployment_id', $this->subject->getLtiDeploymentId());
    }

    public function testGetClientId(): void
    {
        $this->assertEquals('client_id', $this->subject->getClientId());
    }
}
