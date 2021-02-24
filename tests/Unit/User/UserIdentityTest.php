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

namespace OAT\Library\Lti1p3Core\Tests\Unit\User;

use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;
use PHPUnit\Framework\TestCase;

class UserIdentityTest extends TestCase
{
    use DomainTestingTrait;

    public function testGetters(): void
    {
        $subject = $this->createTestUserIdentity();

        $this->assertEquals('userIdentifier', $subject->getIdentifier());
        $this->assertEquals('userName', $subject->getName());
        $this->assertEquals('userEmail', $subject->getEmail());
        $this->assertEquals('userGivenName', $subject->getGivenName());
        $this->assertEquals('userFamilyName', $subject->getFamilyName());
        $this->assertEquals('userMiddleName', $subject->getMiddleName());
        $this->assertEquals('userLocale', $subject->getLocale());
        $this->assertEquals('userPicture', $subject->getPicture());
        $this->assertEmpty($subject->getAdditionalProperties());
    }

    public function testNormalisation(): void
    {
        $subject = $this->createTestUserIdentityWithAdditionalProperties();

        $this->assertEquals(
            [
                'sub' => 'userIdentifier',
                'name' => 'userName',
                'email' => 'userEmail',
                'given_name' => 'userGivenName',
                'family_name' => 'userFamilyName',
                'middle_name' => 'userMiddleName',
                'locale' => 'userLocale',
                'picture' => 'userPicture',
                'property1' => 'value1',
                'property2' => 'value2',
            ],
            $subject->normalize()
        );
    }

    public function testAdditionalProperties(): void
    {
        $subject = $this->createTestUserIdentityWithAdditionalProperties();

        $this->assertEquals(
            [
                'property1' => 'value1',
                'property2' => 'value2'
            ],
            $subject->getAdditionalProperties()->all()
        );

        $this->assertEquals('value1', $subject->getAdditionalProperties()->getMandatory('property1'));
        $this->assertEquals('value2', $subject->getAdditionalProperties()->getMandatory('property2'));

        $this->assertFalse($subject->getAdditionalProperties()->has('missingProperty'));
        $this->assertEquals('default', $subject->getAdditionalProperties()->get('missingProperty', 'default'));
    }

    private function createTestUserIdentityWithAdditionalProperties(): UserIdentityInterface
    {
        return $subject = $this->createTestUserIdentity(
            'userIdentifier',
            'userName',
            'userEmail',
            'userGivenName',
            'userFamilyName',
            'userMiddleName',
            'userLocale',
            'userPicture',
            [
                'property1' => 'value1',
                'property2' => 'value2'
            ]
        );
    }
}
