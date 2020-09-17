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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Builder;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Token\Builder\MessageTokenBuilder;
use OAT\Library\Lti1p3Core\Token\Claim\ResourceLinkTokenClaim;
use OAT\Library\Lti1p3Core\Token\LtiMessageTokenInterface;
use OAT\Library\Lti1p3Core\Token\MessageInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class MessageBuilderTest extends TestCase
{
    use SecurityTestingTrait;

    /** @var MessageTokenBuilder */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new MessageTokenBuilder();
    }

    public function testItCanGenerateAMessageWithRegularClaim(): void
    {
        $message = $this->subject
            ->withClaim('a', 'b')
            ->getMessage($this->createTestKeyChain());

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals('b', $message->getClaim('a'));
    }

    public function testItCanGenerateAMessageWithMessageClaimInterface(): void
    {
        $claim = new ResourceLinkTokenClaim('id');

        $message = $this->subject
            ->withClaim($claim)
            ->getMessage($this->createTestKeyChain());

        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals($claim, $message->getClaim(ResourceLinkTokenClaim::class));
    }

    public function testItCanGenerateAnLtiMessageWithRegularClaim(): void
    {
        $message = $this->subject
            ->withClaim('a', 'b')
            ->getLtiMessage($this->createTestKeyChain());

        $this->assertInstanceOf(LtiMessageTokenInterface::class, $message);
        $this->assertEquals('b', $message->getClaim('a'));
    }

    public function testItCanGenerateAnLtiMessageWithMessageClaimInterface(): void
    {
        $claim = new ResourceLinkTokenClaim('id');

        $message = $this->subject
            ->withClaim($claim)
            ->getLtiMessage($this->createTestKeyChain());

        $this->assertInstanceOf(LtiMessageTokenInterface::class, $message);
        $this->assertEquals($claim, $message->getClaim(ResourceLinkTokenClaim::class));
    }

    public function testItThrowsAnLtiExceptionOnFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot generate message token: It was not possible to parse your key');

        $this->subject->getMessage($this->createTestKeyChain('invalid', 'invalid', 'invalid', 'invalid'));
    }
}
