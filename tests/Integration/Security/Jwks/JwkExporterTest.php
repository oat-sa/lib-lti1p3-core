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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\Jwks;

use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Security\Jwks\JwkExporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use PHPUnit\Framework\TestCase;

class JwksExporterTest extends TestCase
{
    public function testItCanExportAnRSAKeyChain(): void
    {
        $keyChain = new KeyChain(
            '1',
            'setName',
            'file://' . __DIR__ . '/../../../Resource/Key/RSA/public.key',
            'file://' . __DIR__ . '/../../../Resource/Key/RSA/private.key',
            null
        );

        $subject = new JwkExporter();

        $this->assertEquals(
            [
                'alg' => 'RS256',
                'kty' => 'RSA',
                'use' => 'sig',
                'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                'e' => 'AQAB',
                'kid' => '1',
            ],
            $subject->export($keyChain)
        );
    }

    public function testItCannotExportOtherThanRSAKeyChain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key type is not OPENSSL_KEYTYPE_RSA');

        $keyChain = new KeyChain(
            '1',
            'setName',
            'file://' . __DIR__ . '/../../../Resource/Key/DSA/public.key',
            'file://' . __DIR__ . '/../../../Resource/Key/DSA/private.key',
            null
        );

        (new JwkExporter())->export($keyChain);
    }
}
