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

use Exception;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcInitiatorTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var RegistrationRepositoryInterface|MockObject */
    private $repositoryMock;

    /** @var OidcInitiator */
    private $subject;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(RegistrationRepositoryInterface::class);

        $this->subject= new OidcInitiator($this->repositoryMock);
    }

    public function testInitiationFailureOnRegistrationNotFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot find registration for OIDC request');

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByPlatformIssuer')
            ->with('iss', 'client_id')
            ->willReturn(null);

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://tool.com/init?%s', http_build_query([
                'iss' => 'iss',
                'client_id' => 'client_id'
            ]))
        );

        $this->subject->initiate($request);
    }

    public function testInitiationFailureOnRegistrationToolKeyChainNotFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Registration registrationIdentifier does not have a configured tool key chain');

        $registrationMock = $this->createMock(RegistrationInterface::class);
        $registrationMock
            ->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('registrationIdentifier');
        $registrationMock
            ->expects($this->once())
            ->method('getToolKeyChain')
            ->willReturn(null);

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByPlatformIssuer')
            ->with('iss', 'client_id')
            ->willReturn($registrationMock);

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://tool.com/init?%s', http_build_query([
                'iss' => 'iss',
                'client_id' => 'client_id'
            ]))
        );

        $this->subject->initiate($request);
    }

    public function testInitiationFailureOnInvalidLtiDeploymentId(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot find deployment for OIDC request');

        $registration = $this->createTestRegistration();

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByPlatformIssuer')
            ->with('iss', 'client_id')
            ->willReturn($registration);

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://tool.com/init?%s', http_build_query([
                'iss' => 'iss',
                'client_id' => 'client_id',
                'lti_deployment_id' => 'invalid'
            ]))
        );

        $this->subject->initiate($request);
    }

    public function testInitiationFailureOnGenericError(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('OIDC initiation failed: custom error');

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByPlatformIssuer')
            ->with('iss', 'client_id')
            ->willThrowException(new Exception('custom error'));

        $request = $this->createServerRequest(
            'GET',
            sprintf('http://tool.com/init?%s', http_build_query([
                'iss' => 'iss',
                'client_id' => 'client_id'
            ]))
        );

        $this->subject->initiate($request);
    }
}
