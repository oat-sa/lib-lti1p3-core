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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class LaunchPresentationClaimTest extends TestCase
{
    /** @var LaunchPresentationClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new LaunchPresentationClaim('documentTarget', 'height', 'width', 'returnUrl', 'locale');
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_LAUNCH_PRESENTATION, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('documentTarget', $this->subject->getDocumentTarget());
        $this->assertEquals('height', $this->subject->getHeight());
        $this->assertEquals('width', $this->subject->getWidth());
        $this->assertEquals('returnUrl', $this->subject->getReturnUrl());
        $this->assertEquals('locale', $this->subject->getLocale());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'document_target' => 'documentTarget',
                'height' => 'height',
                'width' => 'width',
                'return_url' => 'returnUrl',
                'locale' => 'locale'
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = LaunchPresentationClaim::denormalize([
            'document_target' => 'documentTarget',
            'height' => 'height',
            'width' => 'width',
            'return_url' => 'returnUrl',
            'locale' => 'locale'
        ]);

        $this->assertInstanceOf(LaunchPresentationClaim::class, $denormalisation);
        $this->assertEquals('documentTarget', $denormalisation->getDocumentTarget());
        $this->assertEquals('height', $denormalisation->getHeight());
        $this->assertEquals('width', $denormalisation->getWidth());
        $this->assertEquals('returnUrl', $denormalisation->getReturnUrl());
        $this->assertEquals('locale', $denormalisation->getLocale());
    }
}
