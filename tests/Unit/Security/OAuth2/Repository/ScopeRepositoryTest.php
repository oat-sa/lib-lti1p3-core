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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\OAuth2\Repository;

use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ScopeRepository;
use PHPUnit\Framework\TestCase;

class ScopeRepositoryTest extends TestCase
{
    /** @var ScopeRepository */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new ScopeRepository([new Scope('scope1')]);
    }

    public function testScopes(): void
    {
        $result = $this->subject->getScopeEntityByIdentifier('scope1');
        $this->assertInstanceOf(Scope::class, $result);
        $this->assertEquals('scope1', $result->getIdentifier());

        $this->subject->addScope(new Scope('scope2'));

        $result = $this->subject->getScopeEntityByIdentifier('scope2');
        $this->assertInstanceOf(Scope::class, $result);
        $this->assertEquals('scope2', $result->getIdentifier());

        $this->assertNull($this->subject->getScopeEntityByIdentifier('invalid'));
    }
}
