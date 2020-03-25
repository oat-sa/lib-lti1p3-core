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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Message;

use Lcobucci\JWT\Builder;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /** @var Builder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new Builder();
    }

    public function testGetToken(): void
    {
        $token = $this->builder->getToken();

        $subject = new Message($token);

        $this->assertSame($token, $subject->getToken());
    }

    public function testGetMandatoryClaimSuccessWithRegularClaim(): void
    {
        $token = $this->builder
            ->withClaim('claimName', 'claimValue')
            ->getToken();

        $subject = new Message($token);

        $this->assertEquals('claimValue', $subject->getMandatoryClaim('claimName'));
    }

    public function testGetMandatoryClaimSuccessWithMessageClaimInterface(): void
    {
        $claim = new ResourceLinkClaim('id', 'title', 'description');

        $token = $this->builder
            ->withClaim(ResourceLinkClaim::getClaimName(), $claim->normalize())
            ->getToken();

        $subject = new Message($token);

        $this->assertEquals($claim, $subject->getMandatoryClaim(ResourceLinkClaim::class));
    }

    public function testGetMandatoryClaimFailureOnMissingClaim(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot read mandatory missingClaimName claim: Requested claim is not configured');

        $token = $this->builder->getToken();

        $subject = new Message($token);

        $subject->getMandatoryClaim('missingClaimName');
    }

    public function testGetClaimSuccessWithRegularClaim(): void
    {
        $token = $this->builder
            ->withClaim('claimName', 'claimValue')
            ->getToken();

        $subject = new Message($token);

        $this->assertEquals('claimValue', $subject->getClaim('claimName'));
    }

    public function testGetClaimSuccessWithMissingRegularClaimAndGivenDefaultValue(): void
    {
        $token = $this->builder->getToken();

        $subject = new Message($token);

        $this->assertEquals('claimValue', $subject->getClaim('claimName', 'claimValue'));
    }

    public function testGetClaimSuccessWithMessageClaimInterface(): void
    {
        $claim = new ResourceLinkClaim('id', 'title', 'description');

        $token = $this->builder
            ->withClaim(ResourceLinkClaim::getClaimName(), $claim->normalize())
            ->getToken();

        $subject = new Message($token);

        $this->assertEquals($claim, $subject->getClaim(ResourceLinkClaim::class));
    }

    public function testGetClaimSuccessWithMissingMessageClaimInterfaceAndGivenDefaultValue(): void
    {
        $token = $this->builder->getToken();

        $subject = new Message($token);

        $this->assertEquals('claimValue', $subject->getClaim(ResourceLinkClaim::class, 'claimValue'));
    }
}
