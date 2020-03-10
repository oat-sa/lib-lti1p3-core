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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Platform;

use Carbon\Carbon;
use Exception;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Platform\OidcLoginCreator;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationRequest;
use OAT\Library\Lti1p3Core\Tests\Traits\DeploymentTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcLoginCreatorTest extends TestCase
{
    use DeploymentTestingTrait;

    /** @var DeploymentRepositoryInterface|MockObject */
    private $deploymentRepositoryMock;

    /** @var OidcLoginCreator */
    private $subject;

    public function setUp()
    {
        Carbon::setTestNow(Carbon::create(1988, 12, 22, 06));

        $this->deploymentRepositoryMock = $this->createMock(DeploymentRepositoryInterface::class);

        $this->subject = new OidcLoginCreator($this->deploymentRepositoryMock);
    }

    public function testItCanGenerateALoginInitiationRequest(): void
    {
        $deployment = $this->getTestingDeployment();

        $this->deploymentRepositoryMock
            ->expects($this->once())
            ->method('findByIssuer')
            ->with('issuer')
            ->willReturn($deployment);

        $result = $this->subject->create('issuer', 'loginHint', 'targetLinkUri');

        $this->assertInstanceOf(LoginInitiationRequest::class, $result);

        $this->assertEquals(
            $deployment->getTool()->getOidcLoginInitiationUrl(),
            parse_url($result->buildUrl(), PHP_URL_PATH)
        );

        parse_str(parse_url($result->buildUrl(), PHP_URL_QUERY), $queryParameters);
        $this->assertEquals('issuer', $queryParameters['iss']);
        $this->assertEquals('loginHint', $queryParameters['login_hint']);
        $this->assertEquals('targetLinkUri', $queryParameters['target_link_uri']);
    }

    public function testItThrowALTiExceptionWhenNoDeploymentMatchGivenIssuer(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('Deployment not found for issuer issuer');

        $this->deploymentRepositoryMock
            ->expects($this->once())
            ->method('findByIssuer')
            ->with('issuer')
            ->willReturn(null);

        $this->subject->create('issuer', 'loginHint', 'targetLinkUri');
    }

    public function testItThrowALTiExceptionOnGenericError(): void
    {
        $this->expectException(LtiExceptionInterface::class);
        $this->expectExceptionMessage('OIDC Login Creation error: custom error');

        $this->deploymentRepositoryMock
            ->expects($this->once())
            ->method('findByIssuer')
            ->with('issuer')
            ->willThrowException(new Exception('custom error'));

        $this->subject->create('issuer', 'loginHint', 'targetLinkUri');
    }
}
