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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Flow\Security\Jwt;

use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\BuilderInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;

class JwtIntegrationFlowTest extends TestCase
{
    use DomainTestingTrait;

    /** @var BuilderInterface */
    private $builder;

    /** @var ParserInterface */
    private $parser;

    /** @var ValidatorInterface */
    private $validator;

    protected function setUp(): void
    {
        $this->builder = new Builder();
        $this->parser = new Parser();
        $this->validator = new Validator();
    }

    public function testJwtIntegrationFlow(): void
    {
        $registration = $this->createTestRegistration();

        // Builder

        $token = $this->builder->build(
            [
                MessagePayloadInterface::HEADER_KID => 'kid'
            ],
            [
                MessagePayloadInterface::CLAIM_SUB => 'sub',
                MessagePayloadInterface::CLAIM_ISS => 'iss',
                MessagePayloadInterface::CLAIM_AUD => ['aud1', 'aud2'],
                'randomClaim' => 'randomValue'
            ],
            $registration->getPlatformKeyChain()->getPrivateKey()
        );

        $this->assertInstanceOf(TokenInterface::class, $token);

        $this->assertEquals('kid', $token->getHeaders()->get(MessagePayloadInterface::HEADER_KID));

        $this->assertEquals('sub', $token->getClaims()->get(MessagePayloadInterface::CLAIM_SUB));
        $this->assertEquals('iss', $token->getClaims()->get(MessagePayloadInterface::CLAIM_ISS));
        $this->assertEquals(['aud1', 'aud2'], $token->getClaims()->get(MessagePayloadInterface::CLAIM_AUD));
        $this->assertEquals('randomValue', $token->getClaims()->get('randomClaim'));

        // Parser

        $parsedToken = $this->parser->parse($token->toString());

        $this->assertInstanceOf(TokenInterface::class, $token);

        $this->assertEquals('kid', $parsedToken->getHeaders()->get(MessagePayloadInterface::HEADER_KID));

        $this->assertEquals('sub', $parsedToken->getClaims()->get(MessagePayloadInterface::CLAIM_SUB));
        $this->assertEquals('iss', $parsedToken->getClaims()->get(MessagePayloadInterface::CLAIM_ISS));
        $this->assertEquals(['aud1', 'aud2'], $parsedToken->getClaims()->get(MessagePayloadInterface::CLAIM_AUD));
        $this->assertEquals('randomValue', $parsedToken->getClaims()->get('randomClaim'));

        // Validator

        $this->assertTrue(
            $this->validator->validate($parsedToken, $registration->getPlatformKeyChain()->getPublicKey())
        );
    }
}
