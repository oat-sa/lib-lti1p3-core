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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload\Claim;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\ForUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class ForUserClaimTest extends TestCase
{
    /** @var ForUserClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new ForUserClaim(
            'identifier',
            'name',
            'givenName',
            'familyName',
            'email',
            'personSourcedId',
            [
                'Learner'
            ]
        );
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_FOR_USER, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('identifier', $this->subject->getIdentifier());
        $this->assertEquals('name', $this->subject->getName());
        $this->assertEquals('givenName', $this->subject->getGivenName());
        $this->assertEquals('familyName', $this->subject->getFamilyName());
        $this->assertEquals('email', $this->subject->getEmail());
        $this->assertEquals('personSourcedId', $this->subject->getPersonSourcedId());
        $this->assertEquals(['Learner'], $this->subject->getRoles());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'user_id' => 'identifier',
                'name' => 'name',
                'given_name' => 'givenName',
                'family_name' => 'familyName',
                'email' => 'email',
                'person_sourcedid' => 'personSourcedId',
                'roles' => [
                    'Learner'
                ]
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = ForUserClaim::denormalize([
            'user_id' => 'identifier',
            'name' => 'name',
            'given_name' => 'givenName',
            'family_name' => 'familyName',
            'email' => 'email',
            'person_sourcedid' => 'personSourcedId',
            'roles' => [
                'Learner'
            ]
        ]);

        $this->assertInstanceOf(ForUserClaim::class, $denormalisation);
        $this->assertEquals('identifier', $denormalisation->getIdentifier());
        $this->assertEquals('name', $denormalisation->getName());
        $this->assertEquals('givenName', $denormalisation->getGivenName());
        $this->assertEquals('familyName', $denormalisation->getFamilyName());
        $this->assertEquals('email', $denormalisation->getEmail());
        $this->assertEquals('personSourcedId', $denormalisation->getPersonSourcedId());
        $this->assertEquals(['Learner'], $denormalisation->getRoles());
    }
}
