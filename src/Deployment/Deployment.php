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

namespace OAT\Library\Lti1p3Core\Deployment;

use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#tool-deployment-0
 */
class Deployment implements DeploymentInterface
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $clientId;

    /** @var PlatformInterface */
    private $platform;

    /** @var ToolInterface */
    private $tool;

    /** @var KeyChainInterface|null */
    private $platformKeyChain;

    /** @var KeyChainInterface|null */
    private $toolKeyChain;

    /** @var string|null */
    private $platformJwksUrl;

    /** @var string|null */
    private $toolJwksUrl;

    public function __construct(
        string $identifier,
        string $clientId,
        PlatformInterface $platform,
        ToolInterface $tool,
        KeyChainInterface $platformKeyChain = null,
        KeyChainInterface $toolKeyChain = null,
        string $platformJwksUrl = null,
        string $toolJwksUrl = null
    ) {
        $this->identifier = $identifier;
        $this->clientId = $clientId;
        $this->platform = $platform;
        $this->tool = $tool;
        $this->platformKeyChain = $platformKeyChain;
        $this->toolKeyChain = $toolKeyChain;
        $this->platformJwksUrl = $platformJwksUrl;
        $this->toolJwksUrl = $toolJwksUrl;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
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

    public function getPlatformKeyChain(): ?KeyChainInterface
    {
        return $this->platformKeyChain;
    }

    public function getToolKeyChain(): ?KeyChainInterface
    {
        return $this->toolKeyChain;
    }

    public function getPlatformJwksUrl(): ?string
    {
        return $this->platformJwksUrl;
    }

    public function getToolJwksUrl(): ?string
    {
        return $this->toolJwksUrl;
    }
}
