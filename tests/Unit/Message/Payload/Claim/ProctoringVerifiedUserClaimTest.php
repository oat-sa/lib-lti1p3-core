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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload\Claim;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\ProctoringVerifiedUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class ProctoringVerifiedUserClaimTest extends TestCase
{
    use DomainTestingTrait;

    /** @var ProctoringVerifiedUserClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new ProctoringVerifiedUserClaim(['userData']);
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_VERIFIED_USER, $this->subject::getClaimName());
    }

    public function testFromUserIdentity(): void
    {
        $userIdentity = $this->createTestUserIdentity();

        $subject = ProctoringVerifiedUserClaim::fromUserIdentity($userIdentity);

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
            ],
            $subject->getUserData()
        );
    }

    public function testGetters(): void
    {
        $this->assertEquals(['userData'], $this->subject->getUserData());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(['userData'], $this->subject->normalize());
    }

    public function testDenormalisation(): void
    {
        $denormalisation = ProctoringVerifiedUserClaim::denormalize(['userData']);

        $this->assertInstanceOf(ProctoringVerifiedUserClaim::class, $denormalisation);
        $this->assertEquals(['userData'], $denormalisation->getUserData());
    }
}
