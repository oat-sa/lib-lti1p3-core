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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */


declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwt\Converter;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Jwt\Converter\JWKConverter;
use PHPUnit\Framework\TestCase;

class JWKConverterTest extends TestCase
{
    /** The JWK of tests/Resource/Key/RSA/public.key. */
    private const FIXTURE_JWK = [
        'alg' => 'RS256',
        'kty' => 'RSA',
        'use' => 'sig',
        'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
        'e' => 'AQAB',
        'kid' => 'keyChainIdentifier1',
    ];

    /** @var JWKConverter */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new JWKConverter();
    }

    public function testToPemProducesTheSamePublicKeyAsThePemFixture(): void
    {
        $this->assertSame(
            $this->der(file_get_contents(__DIR__ . '/../../../../Resource/Key/RSA/public.key')),
            $this->der($this->subject->toPEM(self::FIXTURE_JWK))
        );
    }

    public function testToPemFailsOnMissingMembers(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('This JWK cannot be converted to PEM format.');

        $this->subject->toPEM(['kty' => 'RSA', 'n' => self::FIXTURE_JWK['n']]);
    }

    public function testToPemFailsOnNonRsaKeys(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('RSA key type is currently only supported.');

        $this->subject->toPEM(['kty' => 'EC', 'n' => 'eA', 'e' => 'AQAB']);
    }

    public function testToPemFailsOnPrivateKeys(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Public key is currently only supported.');

        $this->subject->toPEM(self::FIXTURE_JWK + ['d' => 'AQAB']);
    }

    private function der(string $pem): string
    {
        return base64_decode(preg_replace('/-----[^-]+-----|\s+/', '', $pem));
    }
}
