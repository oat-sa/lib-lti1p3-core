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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class MessagePayloadBuilderTest extends TestCase
{
    use SecurityTestingTrait;

    /** @var MessagePayloadBuilderInterface */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new MessagePayloadBuilder();
    }

    public function testItCanBeReset(): void
    {
        $payload = $this->subject
            ->withClaim('a', 'b')
            ->reset()
            ->withClaim('c', 'd')
            ->buildMessagePayload($this->createTestKeyChain());

        $this->assertInstanceOf(MessagePayloadInterface::class, $payload);
        $this->assertNull($payload->getClaim('a'));
        $this->assertEquals('d', $payload->getClaim('c'));
    }

    public function testItCanGenerateAMessagePayloadWithRegularClaim(): void
    {
        $payload = $this->subject
            ->withClaim('a', 'b')
            ->buildMessagePayload($this->createTestKeyChain());

        $this->assertInstanceOf(MessagePayloadInterface::class, $payload);
        $this->assertEquals('b', $payload->getClaim('a'));
    }

    public function testItCanGenerateAMessagePayloadWithMessagePayloadClaimInterface(): void
    {
        $claim = new ResourceLinkClaim('id');

        $payload = $this->subject
            ->withClaim($claim)
            ->buildMessagePayload($this->createTestKeyChain());

        $this->assertInstanceOf(MessagePayloadInterface::class, $payload);
        $this->assertEquals($claim, $payload->getClaim(ResourceLinkClaim::class));
    }

    public function testItCanGenerateAMessagePayloadFromMultipleMixedClaims(): void
    {
        $claim = new ResourceLinkClaim('id');

        $claims = [
            'a' => 'b',
            $claim
        ];

        $payload = $this->subject
            ->withClaims($claims)
            ->buildMessagePayload($this->createTestKeyChain());

        $this->assertInstanceOf(MessagePayloadInterface::class, $payload);
        $this->assertEquals('b', $payload->getClaim('a'));
        $this->assertEquals($claim, $payload->getClaim(ResourceLinkClaim::class));
    }

    public function testItCanGenerateAMessagePayloadFromAnotherMessagePayload(): void
    {
        $originalPayload = $this->subject
            ->withClaim('a', 'b')
            ->buildMessagePayload($this->createTestKeyChain());

        $newPaylaod = $this->subject
            ->withClaims($originalPayload->getToken()->getClaims()->all())
            ->withClaim('c', 'd')
            ->buildMessagePayload($this->createTestKeyChain());

        $this->assertInstanceOf(MessagePayloadInterface::class, $newPaylaod);
        $this->assertEquals('b', $newPaylaod->getClaim('a'));
        $this->assertEquals('d', $newPaylaod->getClaim('c'));
    }

    public function testItThrowsAnLtiExceptionOnFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot generate message token: Cannot build token');

        $this->subject->buildMessagePayload($this->createTestKeyChain('invalid', 'invalid', 'invalid', 'invalid'));
    }
}
