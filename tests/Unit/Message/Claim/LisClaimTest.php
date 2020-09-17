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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Claim;

use OAT\Library\Lti1p3Core\Token\Claim\LisTokenClaim;
use OAT\Library\Lti1p3Core\Token\LtiMessageTokenInterface;
use PHPUnit\Framework\TestCase;

class LisClaimTest extends TestCase
{
    /** @var LisTokenClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LisTokenClaim(
            'course_offering_sourcedid',
            'course_section_sourcedid',
            'outcome_service_url',
            'person_sourcedid',
            'result_sourcedid'
        );
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessageTokenInterface::CLAIM_LTI_LIS, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('course_offering_sourcedid', $this->subject->getCourseOfferingSourcedId());
        $this->assertEquals('course_section_sourcedid', $this->subject->getCourseSectionSourcedId());
        $this->assertEquals('outcome_service_url', $this->subject->getOutcomeServiceUrl());
        $this->assertEquals('person_sourcedid', $this->subject->getPersonSourcedId());
        $this->assertEquals('result_sourcedid', $this->subject->getResultSourcedId());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'course_offering_sourcedid' => 'course_offering_sourcedid',
                'course_section_sourcedid' => 'course_section_sourcedid',
                'outcome_service_url' => 'outcome_service_url',
                'person_sourcedid' => 'person_sourcedid',
                'result_sourcedid' => 'result_sourcedid'
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = LisTokenClaim::denormalize([
            'course_offering_sourcedid' => 'course_offering_sourcedid',
            'course_section_sourcedid' => 'course_section_sourcedid',
            'outcome_service_url' => 'outcome_service_url',
            'person_sourcedid' => 'person_sourcedid',
            'result_sourcedid' => 'result_sourcedid'
        ]);

        $this->assertInstanceOf(LisTokenClaim::class, $denormalisation);
        $this->assertEquals('course_offering_sourcedid', $denormalisation->getCourseOfferingSourcedId());
        $this->assertEquals('course_section_sourcedid', $denormalisation->getCourseSectionSourcedId());
        $this->assertEquals('outcome_service_url', $denormalisation->getOutcomeServiceUrl());
        $this->assertEquals('person_sourcedid', $denormalisation->getPersonSourcedId());
        $this->assertEquals('result_sourcedid', $denormalisation->getResultSourcedId());
    }
}
