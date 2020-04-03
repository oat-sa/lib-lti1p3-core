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

namespace OAT\Library\Lti1p3Core\Registration;

use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#tool-deployment-0
 */
interface RegistrationInterface
{
    public function getIdentifier(): string;

    public function getClientId(): string;

    public function getPlatform(): PlatformInterface;

    public function getTool(): ToolInterface;

    public function getDeploymentIds(): array;

    public function hasDeploymentId(string $deploymentId): bool;

    public function getDefaultDeploymentId(): ?string;

    public function getPlatformKeyChain(): ?KeyChainInterface;

    public function getToolKeyChain(): ?KeyChainInterface;

    public function getPlatformJwksUrl(): ?string;

    public function getToolJwksUrl(): ?string;
}
