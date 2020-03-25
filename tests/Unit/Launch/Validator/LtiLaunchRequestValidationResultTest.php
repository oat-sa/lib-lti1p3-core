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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Launch\Validator;

use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidationResult;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use PHPUnit\Framework\TestCase;

class LtiLaunchRequestValidationResultTest extends TestCase
{
    public function testGetLtiMessage(): void
    {
        $ltiMessageMock = $this->createMock(LtiMessageInterface::class);

        $subject = new LtiLaunchRequestValidationResult($ltiMessageMock);

        $this->assertEquals($ltiMessageMock, $subject->getLtiMessage());
    }

    public function testResultBehavior(): void
    {
        $subject = new LtiLaunchRequestValidationResult($this->createMock(LtiMessageInterface::class));

        $this->assertFalse($subject->hasFailures());

        $subject->addSuccess('success');

        $this->assertFalse($subject->hasFailures());

        $subject->addFailure('failure');

        $this->assertTrue($subject->hasFailures());

        $this->assertEquals(['success'], $subject->getSuccesses());
        $this->assertEquals(['failure'], $subject->getFailures());
    }
}
