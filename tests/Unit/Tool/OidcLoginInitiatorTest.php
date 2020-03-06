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
use Exception;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\AuthenticationRequest;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationParameters;
use OAT\Library\Lti1p3Core\Tests\Traits\DeploymentTestingTrait;
use OAT\Library\Lti1p3Core\Tool\OidcLoginInitiator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcLoginInitiatorTest extends TestCase
{
    use DeploymentTestingTrait;

    /** @var DeploymentRepositoryInterface|MockObject */
    private $deploymentRepositoryMock;

    /** @var NonceRepositoryInterface|MockObject */
    private $nonceRepositoryMock;

    /** @var NonceGeneratorInterface|MockObject */
    private $nonceGeneratorMock;

    /** @var OidcLoginInitiator */
    private $subject;

    public function setUp()
    {
        Carbon::setTestNow(Carbon::create(1988, 12, 22, 06));

        $this->deploymentRepositoryMock = $this->createMock(DeploymentRepositoryInterface::class);
        $this->nonceRepositoryMock = $this->createMock(NonceRepositoryInterface::class);
        $this->nonceGeneratorMock = $this->createMock(NonceGeneratorInterface::class);

        $this->subject = new OidcLoginInitiator(
            $this->deploymentRepositoryMock,
            $this->nonceRepositoryMock,
            $this->nonceGeneratorMock
        );
    }

    public function testItCanGenerateAnAuthenticationRequest(): void
    {
        $deployment = $this->getTestingDeployment();

        $loginInitiationParameters = new LoginInitiationParameters(
            'audience',
            'loginHint',
            'targetLinkUri'
        );

        $this->deploymentRepositoryMock
            ->expects($this->once())
            ->method('findByIssuer')
            ->with('audience')
            ->willReturn($deployment);

        $this->nonceGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->willReturn(new Nonce('nonce'));

        $result = $this->subject->initiate($loginInitiationParameters);

        $this->assertInstanceOf(AuthenticationRequest::class, $result);

        $this->assertEquals(
            $deployment->getPlatform()->getOidcAuthenticationUrl(),
            parse_url($result->buildUrl(), PHP_URL_PATH)
        );

        parse_str(parse_url($result->buildUrl(), PHP_URL_QUERY), $queryParameters);
        $this->assertEquals('openid', $queryParameters['scope']);
        $this->assertEquals('id_token', $queryParameters['response_type']);
        $this->assertEquals($deployment->getClientId(), $queryParameters['client_id']);
        $this->assertEquals($loginInitiationParameters->getTargetLinkUri(), $queryParameters['redirect_uri']);
        $this->assertEquals('loginHint', $queryParameters['login_hint']);
        $this->assertEquals('form_post', $queryParameters['response_mode']);
        $this->assertEquals('none', $queryParameters['prompt']);

    }

    public function testItThrowALTiExceptionWhenNoDeploymentMatchGivenIssuer(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('Deployment not found for issuer audience');

        $loginInitiationParameters = new LoginInitiationParameters(
            'audience',
            'loginHint',
            'targetLinkUri'
        );

        $this->deploymentRepositoryMock
            ->expects($this->once())
            ->method('findByIssuer')
            ->with('audience')
            ->willReturn(null);

        $this->subject->initiate($loginInitiationParameters);
    }

    public function testItThrowALTiExceptionOnGenericError(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('OIDC Login Initiation error: custom error');

        $loginInitiationParameters = new LoginInitiationParameters(
            'audience',
            'loginHint',
            'targetLinkUri'
        );

        $this->deploymentRepositoryMock
            ->expects($this->once())
            ->method('findByIssuer')
            ->with('audience')
            ->willThrowException(new Exception('custom error'));

        $this->subject->initiate($loginInitiationParameters);
    }
}
