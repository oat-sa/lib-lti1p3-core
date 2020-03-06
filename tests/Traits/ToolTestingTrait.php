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

use OAT\Library\Lti1p3Core\Tool\ToolInterface;

trait ToolTestingTrait
{
    private function getTestingTool(
        string $name = 'name',
        string $deepLaunchUrl = 'deepLaunchUrl',
        string $oidcLoginInitiationUrl = 'oidcLoginInitiationUrl'
    ): ToolInterface {
        return new class($name, $deepLaunchUrl,$oidcLoginInitiationUrl) implements ToolInterface
        {
            /** @var string */
            private $name;

            /** @var string */
            private $deepLaunchUrl;

            /** @var string */
            private $oidcLoginInitiationUrl;

            public function __construct(string $name, string $deepLaunchUrl, string $oidcLoginInitiationUrl)
            {
                $this->name = $name;
                $this->deepLaunchUrl = $deepLaunchUrl;
                $this->oidcLoginInitiationUrl = $oidcLoginInitiationUrl;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getDeepLaunchUrl(): string
            {
                return $this->deepLaunchUrl;
            }

            public function getOidcLoginInitiationUrl(): string
            {
                return $this->oidcLoginInitiationUrl;
            }
        };
    }
}
