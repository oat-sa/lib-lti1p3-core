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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwt\Parser;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    use DomainTestingTrait;

    /** @var ParserInterface */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new Parser();
    }

    public function testParsingSuccess(): void
    {
        $registration = $this->createTestRegistration();

        $token = $this->buildJwt(
            [
                MessagePayloadInterface::HEADER_KID => 'kid'
            ],
            [
                MessagePayloadInterface::CLAIM_SUB => 'sub',
                MessagePayloadInterface::CLAIM_ISS => 'iss',
                MessagePayloadInterface::CLAIM_AUD => 'aud',
                'claim' => 'value',
                'arrayClaim' => ['value1', 'value2']
            ],
            $registration->getPlatformKeyChain()->getPrivateKey()
        );

        $result = $this->subject->parse($token->toString());

        $this->assertInstanceOf(TokenInterface::class, $result);

        $this->assertEquals('kid', $result->getHeaders()->get(MessagePayloadInterface::HEADER_KID));

        $this->assertEquals('id', $result->getClaims()->get(MessagePayloadInterface::CLAIM_JTI));
        $this->assertEquals('sub', $result->getClaims()->get(MessagePayloadInterface::CLAIM_SUB));
        $this->assertEquals('iss', $result->getClaims()->get(MessagePayloadInterface::CLAIM_ISS));
        $this->assertEquals(['aud'], $result->getClaims()->get(MessagePayloadInterface::CLAIM_AUD));
        $this->assertEquals('value', $result->getClaims()->get('claim'));
        $this->assertEquals(['value1', 'value2'], $result->getClaims()->get('arrayClaim'));
    }

    public function testParsingFailure(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot parse token: The JWT string must have two dots');

        $this->subject->parse('invalid');
    }
}
