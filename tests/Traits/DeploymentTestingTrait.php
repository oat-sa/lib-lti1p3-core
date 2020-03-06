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

use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;

trait DeploymentTestingTrait
{
    use PlatformTestingTrait;
    use ToolTestingTrait;
    use KeyChainTestingTrait;

    private function getTestingDeployment(
        string $id = 'deploymentId',
        string $clientId = 'clientId',
        PlatformInterface $platform = null,
        ToolInterface $tool = null,
        string $platformJwksUrl = 'platformJwksUrl',
        KeyChainInterface $toolKeyPair = null,
        KeyChainInterface $platformKeyPair = null
    ): DeploymentInterface {

        $platform = $platform ?? $this->getTestingPlatform();
        $tool = $tool ?? $this->getTestingTool();
        $toolKeyPair = $toolKeyPair ?? $this->getTestingKeyChain('tool');
        $platformKeyPair = $platformKeyPair ?? $this->getTestingKeyChain('platform');

        return new class (
            $id,
            $clientId,
            $platform,
            $tool,
            $platformJwksUrl,
            $toolKeyPair,
            $platformKeyPair
        ) implements DeploymentInterface {
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

            public function __construct(
                string $id,
                string $clientId,
                PlatformInterface $platform,
                ToolInterface $tool,
                string $platformJwksUrl,
                KeyChainInterface $toolKeyPair,
                KeyChainInterface $platformKeyPair = null
            ) {
                $this->id = $id;
                $this->clientId = $clientId;
                $this->platform = $platform;
                $this->tool = $tool;
                $this->platformJwksUrl = $platformJwksUrl;
                $this->toolKeyPair = $toolKeyPair;
                $this->platformKeyPair = $platformKeyPair;
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
}
