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
use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;
use PHPUnit\Framework\TestCase;

trait DeploymentTestingTrait
{
    use PlatformTestingTrait;
    use ToolTestingTrait;
    use KeyChainTestingTrait;

    public function getTestingDeployment(array $parameters = [])
    {
        $testingPlatform = $this->getTestingPlatform();
        $testingTool = $this->getTestingTool();
        $testingToolKeyChain = $this->getTestingToolKeyChain();

        return new class($parameters, $testingPlatform, $testingTool, $testingToolKeyChain) implements DeploymentInterface {
            /** @var string */
            private $id;

            /** @var string */
            private $clientId;

            /** @var PlatformInterface */
            private $platform;

            /** @var ToolInterface */
            private $tool;

            /** @var string */
            private $platformJwksUrl;

            /** @var KeyChainInterface */
            private $toolKeyPair;

            /** @var KeyChainInterface|null */
            private $platformKeyPair;

            public function __construct($parameters, $testingPlatform, $testingTool, $testingToolKeyChain)
            {
                $this->id = $parameters['getId'] ?? 'name';
                $this->clientId = $parameters['getClientId'] ?? 'audience';
                $this->platform = $parameters['getPlatform'] ?? $testingPlatform;
                $this->tool = $parameters['getTool'] ?? $testingTool;
                $this->platformJwksUrl = $parameters['getPlatformJwksUrl'] ?? 'platform_jwks_url';
                $this->toolKeyPair = $parameters['getToolKeyPair'] ?? $testingToolKeyChain;
                $this->platformKeyPair = $parameters['getPlatformKeyPair'] ?? 'oidc_authentication_url';
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getClientId(): string
            {
                return $this->clientId;
            }

            public function getPlatform(): PlatformInterface
            {
                return $this->platform;
            }

            public function getTool(): ToolInterface
            {
                return $this->tool;
            }

            public function getPlatformJwksUrl(): string
            {
                return $this->platformJwksUrl;
            }

            public function getToolKeyPair(): KeyChainInterface
            {
                return $this->toolKeyPair;
            }

            public function getPlatformKeyPair(): ?KeyChainInterface
            {
                return $this->platformKeyPair;
            }
        };
    }

    public function getDeploymentMock(): DeploymentInterface
    {
        /** @var TestCase $this */
        $mockDeployment = $this->createMock(DeploymentInterface::class);
        $mockDeployment->method('getId')->willReturn('id');
        $mockDeployment->method('getClientId')->willReturn('client_id');
        $mockDeployment->method('getPlatform')->willReturn($this->getPlatform());
        $mockDeployment->method('getTool')->willReturn($this->getTool());
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
