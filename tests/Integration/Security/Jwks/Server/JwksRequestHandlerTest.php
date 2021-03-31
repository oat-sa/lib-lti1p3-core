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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\Jwks\Server;

use Exception;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Server\JwksRequestHandler;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Tests\Resource\Logger\TestLogger;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class JwksRequestHandlerTest extends TestCase
{
    use SecurityTestingTrait;

    public function testJwksSuccessResponse(): void
    {
        $repository = new KeyChainRepository([
            $this->createTestKeyChain('keyChainIdentifier', 'keySetName')
        ]);

        $subject = new JwksRequestHandler(new JwksExporter($repository));

        $this->assertEquals(
            [
                'keys' => [
                    [
                        'alg' => 'RS256',
                        'kty' => 'RSA',
                        'use' => 'sig',
                        'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                        'e' => 'AQAB',
                        'kid' => 'keyChainIdentifier',
                    ]
                ]
            ],
            json_decode((string)$subject->handle('keySetName')->getBody(), true)
        );
    }

    public function testJwksErrorResponseOnInvalidExport(): void
    {
        $exporterMock = $this->createMock(JwksExporter::class);
        $exporterMock
            ->expects($this->once())
            ->method('export')
            ->with('keySetName')
            ->willThrowException(new Exception('custom error'));

        $logger = new TestLogger();

        $subject = new JwksRequestHandler($exporterMock, null, $logger);

        $response = $subject->handle('keySetName');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal JWKS server error', (string)$response->getBody());
        $this->assertTrue($logger->hasLog(LogLevel::ERROR, 'Error during JWKS server handling: custom error'));
    }
}
