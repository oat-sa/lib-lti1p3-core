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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Oidc;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationParameters;
use OAT\Library\Lti1p3Core\Security\Oidc\StateGenerator;
use OAT\Library\Lti1p3Core\Tests\Traits\DeploymentTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Unit\Helper\LoginInitiationParametersHelper;
use PHPUnit\Framework\TestCase;

class StateGeneratorTest extends TestCase
{
    use DeploymentTestingTrait;

    /** @var LoginInitiationParameters */
    private $loginInitiationParameters;

    public function setUp()
    {
        $knownDate = Carbon::create(1988, 12, 22, 06);
        Carbon::setTestNow($knownDate);

        $this->loginInitiationParameters = LoginInitiationParametersHelper::getLoginInitiationParameters();
    }

    public function testItCanGenerateAState(): void
    {
        $state = (new StateGenerator())->generate(
            $this->getDeploymentMock(),
            LoginInitiationParametersHelper::getLoginInitiationParameters()
        );

        $statePayload = explode('.', $state);

        $this->assertCount(3, $statePayload);

        $this->assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9',
            $statePayload[0]
        );

        $this->assertStringContainsString(
            'LCJhdWQiOiJvX2F1dGgyX2FjY2Vzc190b2tlbl91cmwiLCJwYXJhbXMiOnsiaXNzIjoiaXNzdWVyIiwibG9naW5faGludCI6ImxvZ2luSGludCIsInRhcmdldF9saW5rX3VyaSI6InRhcmdldExpbmtVcmkiLCJsdGlfbWVzc2FnZV9oaW50IjoibHRpTWVzc2FnZUhpbnQiLCJsdGlfZGVwbG95bWVudF9pZCI6Imx0aURlcGxveW1lbnRJZCIsImNsaWVudF9pZCI6ImNsaWVudElkIn19',
            $statePayload[1]
        );

        $this->assertEquals(342, strlen($statePayload[2]));
    }

    public function testItThrowLtiExceptions(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('State generation failed: custom error');

        (new StateGenerator())->generate(
            $this->getDeploymentExceptionOnMethod('getClientId'),
            LoginInitiationParametersHelper::getLoginInitiationParameters()
        );
    }
}
