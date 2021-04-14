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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Launch\Validator\Result;

use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResult;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResultInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use PHPUnit\Framework\TestCase;

class LaunchValidationResultTest extends TestCase
{
    public function testImplementation(): void
    {
        $subject = new LaunchValidationResult($this->createMock(RegistrationInterface::class));

        $this->assertInstanceOf(LaunchValidationResultInterface::class, $subject);
    }

    public function testGetRegistration(): void
    {
        $registrationMock = $this->createMock(RegistrationInterface::class);

        $subject = new LaunchValidationResult($registrationMock);

        $this->assertEquals($registrationMock, $subject->getRegistration());
    }

    public function testGetPayload(): void
    {
        $payloadMock = $this->createMock(LtiMessagePayloadInterface::class);

        $subject = new LaunchValidationResult(null, $payloadMock);

        $this->assertEquals($payloadMock, $subject->getPayload());
    }

    public function testGetState(): void
    {
        $stateMock = $this->createMock(MessagePayloadInterface::class);

        $subject = new LaunchValidationResult(null, null, $stateMock);

        $this->assertEquals($stateMock, $subject->getState());
    }

    public function testLifecycle(): void
    {
        $subject = new LaunchValidationResult();

        $this->assertFalse($subject->hasError());

        $subject->addSuccess('success');

        $this->assertFalse($subject->hasError());

        $subject->setError('error');

        $this->assertTrue($subject->hasError());

        $this->assertEquals(['success'], $subject->getSuccesses());
        $this->assertEquals('error', $subject->getError());
    }
}
