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

use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\TestCase;

class DeepLinkingSettingsClaimTest extends TestCase
{
    /** @var DeepLinkingSettingsClaim */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new DeepLinkingSettingsClaim(
            'deepLinkingReturnUrl',
            ['ltiResourceLink', 'link', 'image'],
            ['window'],
            'text/html',
            false,
            true,
            'title',
            'text',
            'data'
        );
    }

    public function testGetClaimName(): void
    {
        $this->assertEquals(LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_SETTINGS, $this->subject::getClaimName());
    }

    public function testGetters(): void
    {
        $this->assertEquals('deepLinkingReturnUrl', $this->subject->getDeepLinkingReturnUrl());
        $this->assertEquals(['ltiResourceLink', 'link', 'image'], $this->subject->getAcceptedTypes());
        $this->assertEquals(['window'], $this->subject->getAcceptedPresentationDocumentTargets());
        $this->assertEquals('text/html', $this->subject->getAcceptedMediaTypes());
        $this->assertFalse($this->subject->shouldAcceptMultiple());
        $this->assertTrue($this->subject->shouldAutoCreate());
        $this->assertEquals('title', $this->subject->getTitle());
        $this->assertEquals('text', $this->subject->getText());
        $this->assertEquals('data', $this->subject->getData());
    }

    public function testNormalisation(): void
    {
        $this->assertEquals(
            [
                'deep_link_return_url' => 'deepLinkingReturnUrl',
                'accept_types' => ['ltiResourceLink', 'link', 'image'],
                'accept_presentation_document_targets' => ['window'],
                'accept_media_types' => 'text/html',
                'accept_multiple' => false,
                'auto_create' => true,
                'title' => 'title',
                'text' => 'text',
                'data' => 'data',
            ],
            $this->subject->normalize()
        );
    }

    public function testDenormalisation(): void
    {
        $denormalisation = DeepLinkingSettingsClaim::denormalize([
            'deep_link_return_url' => 'deepLinkingReturnUrl',
            'accept_types' => ['ltiResourceLink', 'link', 'image'],
            'accept_presentation_document_targets' => ['window'],
            'accept_media_types' => 'text/html',
            'accept_multiple' => false,
            'auto_create' => true,
            'title' => 'title',
            'text' => 'text',
            'data' => 'data',
        ]);

        $this->assertInstanceOf(DeepLinkingSettingsClaim::class, $denormalisation);
        $this->assertEquals('deepLinkingReturnUrl', $denormalisation->getDeepLinkingReturnUrl());
        $this->assertEquals(['ltiResourceLink', 'link', 'image'], $denormalisation->getAcceptedTypes());
        $this->assertEquals(['window'], $denormalisation->getAcceptedPresentationDocumentTargets());
        $this->assertEquals('text/html', $denormalisation->getAcceptedMediaTypes());
        $this->assertFalse($denormalisation->shouldAcceptMultiple());
        $this->assertTrue($denormalisation->shouldAutoCreate());
        $this->assertEquals('title', $denormalisation->getTitle());
        $this->assertEquals('text', $denormalisation->getText());
        $this->assertEquals('data', $denormalisation->getData());
    }

    public function testDenormalisationWithStringBooleans(): void
    {
        $denormalisation = DeepLinkingSettingsClaim::denormalize([
            'deep_link_return_url' => 'deepLinkingReturnUrl',
            'accept_types' => ['ltiResourceLink', 'link', 'image'],
            'accept_presentation_document_targets' => ['window'],
            'accept_media_types' => 'text/html',
            'accept_multiple' => 'false',
            'auto_create' => 'true',
        ]);

        $this->assertInstanceOf(DeepLinkingSettingsClaim::class, $denormalisation);
        $this->assertFalse($denormalisation->shouldAcceptMultiple());
        $this->assertTrue($denormalisation->shouldAutoCreate());
    }

    public function testDenormalisationWithMissingBooleans(): void
    {
        $denormalisation = DeepLinkingSettingsClaim::denormalize([
            'deep_link_return_url' => 'deepLinkingReturnUrl',
            'accept_types' => ['ltiResourceLink', 'link', 'image'],
            'accept_presentation_document_targets' => ['window'],
            'accept_media_types' => 'text/html',
        ]);

        $this->assertInstanceOf(DeepLinkingSettingsClaim::class, $denormalisation);
        $this->assertTrue($denormalisation->shouldAcceptMultiple());
        $this->assertFalse($denormalisation->shouldAutoCreate());
    }
}
