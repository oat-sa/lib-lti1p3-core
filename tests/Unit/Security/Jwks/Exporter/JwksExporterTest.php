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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwks\Exporter;

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;

class JwksExporterTest extends TestCase
{
    use SecurityTestingTrait;

    /** @var JwksExporter */
    private $subject;

    protected function setUp(): void
    {
        $repository = new KeyChainRepository([
            $this->createTestKeyChain('keyChainIdentifier1', 'keySetName1'),
            $this->createTestKeyChain('keyChainIdentifier2', 'keySetName2'),
            $this->createTestKeyChain('keyChainIdentifier3', 'keySetName1')
        ]);

        $this->subject = new JwksExporter($repository);
    }

    public function testItCanExportRS256KeyChainsGroupedByKeySetName(): void
    {
        $this->assertEquals(
            [
                'keys' => [
                    [
                        'alg' => 'RS256',
                        'kty' => 'RSA',
                        'use' => 'sig',
                        'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                        'e' => 'AQAB',
                        'kid' => 'keyChainIdentifier1',
                    ],
                    [
                        'alg' => 'RS256',
                        'kty' => 'RSA',
                        'use' => 'sig',
                        'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                        'e' => 'AQAB',
                        'kid' => 'keyChainIdentifier3',
                    ]
                ]
            ],
            $this->subject->export('keySetName1')
        );

        $this->assertEquals(
            [
                'keys' => [
                    [
                        'alg' => 'RS256',
                        'kty' => 'RSA',
                        'use' => 'sig',
                        'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                        'e' => 'AQAB',
                        'kid' => 'keyChainIdentifier2',
                    ]
                ]
            ],
            $this->subject->export('keySetName2')
        );
    }
}
