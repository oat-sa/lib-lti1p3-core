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

use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequestParameters;
use PHPUnit\Framework\TestCase;

class LoginInitiationParametersTest extends TestCase
{
    /** @var LoginInitiationRequestParameters  */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LoginInitiationRequestParameters(
            'issuer',
            'loginHint',
            'targetLinkUri',
            'ltiMessageHint',
            'ltiDeploymentId',
            'clientId'
        );
    }

    public function testGetIssuer(): void
    {
        $this->assertEquals('issuer', $this->subject->getIssuer());
    }

    public function testGetLoginHint(): void
    {
        $this->assertEquals('loginHint', $this->subject->getLoginHint());
    }

    public function testGetTargetLinkUri(): void
    {
        $this->assertEquals('targetLinkUri', $this->subject->getTargetLinkUri());
    }

    public function testGetLtiMessageHint(): void
    {
        $this->assertEquals('ltiMessageHint', $this->subject->getLtiMessageHint());
    }

    public function testGetLtiDeploymentId(): void
    {
        $this->assertEquals('ltiDeploymentId', $this->subject->getLtiDeploymentId());
    }

    public function testGetClientId(): void
    {
        $this->assertEquals('clientId', $this->subject->getClientId());
    }
}
