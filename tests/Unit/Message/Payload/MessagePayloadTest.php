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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Message\Payload;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class MessagePayloadTest extends TestCase
{
    use DomainTestingTrait;

    /** @var MessagePayloadBuilderInterface */
    private $builder;

    /** @var ContextClaim */
    private $claim;

    /** @var MessagePayloadInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->builder = new MessagePayloadBuilder();
        $this->claim = new ContextClaim('identifier');

        $this->subject = $this->builder
            ->withClaim('a', 'b')
            ->withClaim($this->claim)
            ->buildMessagePayload($this->createTestRegistration()->getPlatformKeyChain());
    }

    public function testGetMandatoryClaim(): void
    {
        $this->assertEquals('b', $this->subject->getMandatoryClaim('a'));
        $this->assertEquals($this->claim, $this->subject->getMandatoryClaim(ContextClaim::class));

        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot get mandatory invalid claim');

        $this->subject->getMandatoryClaim('invalid');
    }

    public function testGetClaim(): void
    {
        $this->assertEquals('b', $this->subject->getClaim('a'));
        $this->assertEquals($this->claim, $this->subject->getClaim(ContextClaim::class));

        $this->assertNull($this->subject->getClaim('invalid'));
    }

    public function testHasClaim(): void
    {
        $this->assertTrue($this->subject->hasClaim('a'));
        $this->assertTrue($this->subject->hasClaim(ContextClaim::class));

        $this->assertFalse($this->subject->hasClaim('invalid'));
    }
}
