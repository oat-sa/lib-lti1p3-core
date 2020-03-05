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

use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use PHPUnit\Framework\TestCase;

trait PlatformMockTrait
{
    public function getPlatformMock(): PlatformInterface
    {
        /** @var TestCase $this */
        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->method('getName')->willReturn('name');
        $mockPlatform->method('getAudience')->willReturn('audience');
        $mockPlatform->method('getOAuth2AccessTokenUrl')->willReturn('o_auth2_access_token_url');
        $mockPlatform->method('getOidcAuthenticationUrl')->willReturn('oidc_authentication_url');

        return $mockPlatform;
    }
}
