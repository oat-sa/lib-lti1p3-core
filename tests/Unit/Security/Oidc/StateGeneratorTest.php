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
use Carbon\CarbonInterface;
use DateTimeInterface;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Ecdsa\Sha512;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationParameters;
use OAT\Library\Lti1p3Core\Security\Oidc\StateGenerator;
use OAT\Library\Lti1p3Core\Security\Oidc\StateGeneratorInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DeploymentTestingTrait;
use PHPUnit\Framework\TestCase;

class StateGeneratorTest extends TestCase
{
    use DeploymentTestingTrait;

    /** @var DateTimeInterface|CarbonInterface */
    private $testDate;

    protected function setUp(): void
    {
        $this->testDate = Carbon::create(1988, 12, 22, 06);
        Carbon::setTestNow($this->testDate);
    }

    public function testItCanGenerateAState(): void
    {
        $subject = new StateGenerator();

        $deployment = $this->getTestingDeployment();
        $loginInitiationParameters = new LoginInitiationParameters(
            'audience',
            'loginHint',
            'targetLinkUri',
            'ltiMessageHint',
            'ltiDeploymentId',
        );

        $token = (new Parser())->parse(
            $subject->generate($deployment, $loginInitiationParameters)
        );

        $this->assertEquals('RS256', $token->getHeader('alg'));
        $this->assertEquals($this->testDate->getTimestamp(), $token->getClaim('iat'));
        $this->assertEquals(
            $this->testDate->addSeconds(StateGeneratorInterface::DEFAULT_TTL)->getTimestamp(),
            $token->getClaim('exp')
        );

        $parametersClaim = (array)$token->getClaim('params');
        $this->assertEquals($deployment->getPlatform()->getAudience(), $parametersClaim['iss']);
        $this->assertEquals($loginInitiationParameters->getLoginHint(), $parametersClaim['login_hint']);
        $this->assertEquals($loginInitiationParameters->getTargetLinkUri(), $parametersClaim['target_link_uri']);
        $this->assertEquals($loginInitiationParameters->getLtiMessageHint(), $parametersClaim['lti_message_hint']);
        $this->assertEquals($loginInitiationParameters->getLtiDeploymentId(), $parametersClaim['lti_deployment_id']);
        $this->assertNull($parametersClaim['client_id']);
    }

    public function testItThrowsALtiExceptionWithUnexpectedSigner(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('State generation failed: This key is not compatible with this signer');

        $subject = new StateGenerator(null, new Sha512());

        $subject->generate(
            $this->getTestingDeployment(),
            new LoginInitiationParameters('audience', 'loginHint', 'targetLinkUri')
        );
    }
}
