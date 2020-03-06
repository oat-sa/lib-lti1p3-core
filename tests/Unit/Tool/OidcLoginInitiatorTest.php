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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Tool;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequest;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationParameters;
use OAT\Library\Lti1p3Core\Tests\Traits\DeploymentRepositoryMockTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NonceRepositoryMockTrait;
use OAT\Library\Lti1p3Core\Tests\Unit\Helper\LoginInitiationParametersHelper;
use OAT\Library\Lti1p3Core\Tool\OidcLoginInitiator;
use PHPUnit\Framework\TestCase;

class OidcLoginInitiatorTest extends TestCase
{
    use DeploymentRepositoryMockTrait;
    use NonceRepositoryMockTrait;

    /** @var LoginInitiationParameters */
    private $loginInitiationParameters;

    public function setUp()
    {
        $knownDate = Carbon::create(1988, 12, 22, 06);
        Carbon::setTestNow($knownDate);
    }

    public function testItThrowALtiExceptionWhenNoDeploymentFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Deployment not found for issuer issuer');

        (new OidcLoginInitiator(
            $this->getEmptyDeploymentRepository(),
            $this->getNonceRepositoryMock()
        ))->initiate(LoginInitiationParametersHelper::getLoginInitiationParameters());
    }

    public function testItThrowALtiExceptionOnThrowableCatch(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('OIDC Login Initiation error: custom error');

        (new OidcLoginInitiator(
            $this->getDeploymentRepositoryExceptionOnMethod('findByIssuer'),
            $this->getNonceRepositoryMock()
        ))->initiate(LoginInitiationParametersHelper::getLoginInitiationParameters());
    }

    public function testItGeneratesABuildableAuthenticationRequestUrl(): void
    {
        $authenticationRequest = (new OidcLoginInitiator(
            $this->getDeploymentRepositoryMock(),
            $this->getNonceRepositoryMock()
        ))->initiate(LoginInitiationParametersHelper::getLoginInitiationParameters());

        $this->assertInstanceOf(AuthenticationRequest::class, $authenticationRequest);
    }
}
