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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\AcsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class AcsClaimTest extends TestCase
{
    /** @var AcsClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new AcsClaim(['action1, action2'], 'assessmentControlUrl');
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_ACS, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals(['action1, action2'], $this->subject->getActions());
        $this->assertEquals('assessmentControlUrl', $this->subject->getAssessmentControlUrl());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'actions' => ['action1, action2'],
                'assessment_control_url' => 'assessmentControlUrl',
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = AcsClaim::denormalize([
            'actions' => ['action1, action2'],
            'assessment_control_url' => 'assessmentControlUrl',
        ]);

        $this->assertInstanceOf(AcsClaim::class, $denormalisation);
        $this->assertEquals(['action1, action2'], $denormalisation->getActions());
        $this->assertEquals('assessmentControlUrl', $denormalisation->getAssessmentControlUrl());
    }
}
