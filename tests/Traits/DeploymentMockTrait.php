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

namespace OAT\Library\Lti1p3Core\Tests\Traits;

use Exception;
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Tests\Unit\Helper\KeyChainHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

trait DeploymentMockTrait
{
    use PlatformMockTrait;
    use ToolMockTrait;

    public function getDeploymentMock(): DeploymentInterface
    {
        /** @var TestCase $this */
        $mockDeployment = $this->createMock(DeploymentInterface::class);
        $mockDeployment->method('getId')->willReturn('id');
        $mockDeployment->method('getClientId')->willReturn('client_id');
        $mockDeployment->method('getPlatform')->willReturn($this->getPlatformMock());
        $mockDeployment->method('getTool')->willReturn($this->getToolMock());
        $mockDeployment->method('getPlatformJwksUrl')->willReturn('platform_jwks_url');
        $mockDeployment->method('getToolKeyPair')->willReturn(KeyChainHelper::getToolKeyChain());
        $mockDeployment->method('getPlatformKeyPair')->willReturn(KeyChainHelper::getPlatformKeyChain());

        return $mockDeployment;
    }

    public function getDeploymentExceptionOnMethod(string $method): DeploymentInterface
    {
        $mockDeployment = $this->getDeploymentMock();
        $mockDeployment->method($method)->willThrowException(new Exception('custom error'));

        return $mockDeployment;
    }
}
