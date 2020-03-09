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
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\LtiMessageHintGenerator;
use OAT\Library\Lti1p3Core\Security\Oidc\LtiMessageHintGeneratorInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DeploymentTestingTrait;
use PHPUnit\Framework\TestCase;

class LtiMessageHintGeneratorTest extends TestCase
{
    use DeploymentTestingTrait;

    /** @var DateTimeInterface|CarbonInterface */
    private $testDate;

    protected function setUp(): void
    {
        $this->testDate = Carbon::create(1988, 12, 22, 06);
        Carbon::setTestNow($this->testDate);
    }

    public function testItCanGenerateALtiMessageHint(): void
    {
        $subject = new LtiMessageHintGenerator();

        $deployment = $this->getTestingDeployment();
        $token = (new Parser())->parse($subject->generate($deployment)->__toString());

        $this->assertTrue($token->verify(new Sha256(), $deployment->getPlatformKeyPair()->getPublicKey()));

        $this->assertEquals('RS256', $token->getHeader('alg'));
        $this->assertEquals($this->testDate->getTimestamp(), $token->getClaim('iat'));
        $this->assertEquals(
            $this->testDate->addSeconds(LtiMessageHintGeneratorInterface::DEFAULT_TTL)->getTimestamp(),
            $token->getClaim('exp')
        );

        $this->assertEquals($deployment->getId(), $token->getClaim('deployment_id'));
        $this->assertEquals($deployment->getPlatform()->getName(), $token->getClaim('iss'));
        $this->assertEquals($deployment->getClientId(), $token->getClaim('sub'));
        $this->assertEquals($deployment->getTool()->getOidcLoginInitiationUrl(), $token->getClaim('aud'));
    }

    public function testItThrowsALtiExceptionWithUnexpectedSigner(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('Lti message hint generation failed: This key is not compatible with this signer');

        $subject = new LtiMessageHintGenerator(null, new Sha512());

        $subject->generate($this->getTestingDeployment());
    }

    public function testItThrowsALtiExceptionWithoutPlatformKeyPair(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('Deployment id deploymentId does not have a platform key pair configured');

        $subject = new LtiMessageHintGenerator(null, new Sha512());
        $deployment = $this->getTestingDeployment()->setPlatformKeyPair();

        $subject->generate($deployment);
    }
}
