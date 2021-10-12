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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload\Extractor;

use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Extractor\MessagePayloadClaimsExtractor;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class MessagePayloadExtractorTest extends TestCase
{
    use DomainTestingTrait;

    /** @var MessagePayloadBuilderInterface */
    private $builder;

    /** @var MessagePayloadInterface */
    private $message;

    protected function setUp(): void
    {
        $this->builder = new MessagePayloadBuilder();

        $this->message = $this->builder
            ->withClaim(MessagePayloadInterface::CLAIM_SUB, 'sub')
            ->withClaim(MessagePayloadInterface::CLAIM_AUD, 'aud')
            ->withClaim('customClaim', 'customValue')
            ->withClaim(new ResourceLinkClaim('resourceLinkIdentifier'))
            ->withClaim(new ContextClaim('contextIdentifier'))
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());
    }

    public function testExtraction(): void
    {
        $extractedClaims = MessagePayloadClaimsExtractor::extract($this->message);

        foreach (MessagePayloadClaimsExtractor::DEFAULT_EXCLUDED_CLAIMS as $defaultExcludedClaim) {
            $this->assertArrayNotHasKey($defaultExcludedClaim, $extractedClaims);
        }

        $this->assertEquals(
            [
                MessagePayloadInterface::CLAIM_SUB => 'sub',
                MessagePayloadInterface::CLAIM_AUD => [
                    'aud',
                ],
                'customClaim' => 'customValue',
                LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => [
                    'id' => 'resourceLinkIdentifier',
                ],
                LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT => [
                    'id' => 'contextIdentifier',
                ],
            ],
            $extractedClaims
        );
    }

    public function testExtractionFromLtiMessagePayload(): void
    {
        $ltiMessagePayload = new LtiMessagePayload($this->message->getToken());

        $extractedClaims = MessagePayloadClaimsExtractor::extract($ltiMessagePayload);

        foreach (MessagePayloadClaimsExtractor::DEFAULT_EXCLUDED_CLAIMS as $defaultExcludedClaim) {
            $this->assertArrayNotHasKey($defaultExcludedClaim, $extractedClaims);
        }

        $this->assertEquals(
            [
                MessagePayloadInterface::CLAIM_SUB => 'sub',
                MessagePayloadInterface::CLAIM_AUD => [
                    'aud',
                ],
                'customClaim' => 'customValue',
                LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => [
                    'id' => 'resourceLinkIdentifier',
                ],
                LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT => [
                    'id' => 'contextIdentifier',
                ],
            ],
            $extractedClaims
        );
    }

    public function testExtractionWithExclusions(): void
    {
        $extractedClaims = MessagePayloadClaimsExtractor::extract(
            $this->message,
            [
                MessagePayloadInterface::CLAIM_SUB,
                'customClaim',
                LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT
            ]
        );

        foreach (MessagePayloadClaimsExtractor::DEFAULT_EXCLUDED_CLAIMS as $defaultExcludedClaim) {
            $this->assertArrayNotHasKey($defaultExcludedClaim, $extractedClaims);
        }

        $this->assertEquals(
            [
                MessagePayloadInterface::CLAIM_AUD => [
                    'aud',
                ],
                LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK => [
                    'id' => 'resourceLinkIdentifier',
                ],
            ],
            $extractedClaims
        );
    }
}
