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

use OAT\Library\Lti1p3Core\Security\Jwks\JwkExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\KeyChainTestingTrait;
use PHPUnit\Framework\TestCase;

class JwksExporterTest extends TestCase
{
    use KeyChainTestingTrait;

    /** @var JwksExporter */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new JwksExporter(
            $this->buildKeyChainRepository([
                $this->getTestingKeyChain('1', 'setName'),
                $this->getTestingKeyChain('2', 'otherSetName'),
                $this->getTestingKeyChain('3', 'setName')
            ]),
            new JwkExporter()
        );
    }

    public function testItCanExportAnValidRSAKeySet(): void
    {
        $export = $this->subject->export('setName');

        $this->assertCount(2, $export['keys']);

        $key1 = current($export['keys']);
        $key2 = next($export['keys']);

        $this->assertEquals(
            [
                'alg' => 'RS256',
                'kty' => 'RSA',
                'use' => 'sig',
                'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                'e' => 'AQAB',
                'kid' => '1',
            ],
            $key1
        );

        $this->assertEquals(
            [
                'alg' => 'RS256',
                'kty' => 'RSA',
                'use' => 'sig',
                'n' => 'yZXlfd5yqChtTH91N76VokquRu2r1EwNDUjA0GAygrPzCpPbYokasxzs-60Do_lyTIgd7nRzudAzHnujIPr8GOPIlPlOKT8HuL7xQEN6gmUtz33iDhK97zK7zOFEmvS8kYPwFAjQ03YKv-3T9b_DbrBZWy2Vx4Wuxf6mZBggKQfwHUuJxXDv79NenZarUtC5iFEhJ85ovwjW7yMkcflhUgkf1o_GIR5RKoNPttMXhKYZ4hTlLglMm1FgRR63pvYoy9Eq644a9x2mbGelO3HnGbkaFo0HxiKbFW1vplHzixYCyjc15pvtBxw_x26p8-lNthuxzaX5HaFMPGs10rRPLw',
                'e' => 'AQAB',
                'kid' => '3',
            ],
            $key2
        );
    }

    public function testItCannotExportAnInvalidRSAKeySet(): void
    {
        $export = $this->subject->export('invalid');

        $this->assertEmpty($export['keys']);
    }

    private function buildKeyChainRepository(array $keyChains): KeyChainRepositoryInterface
    {
        return new class ($keyChains) implements KeyChainRepositoryInterface
        {
            /** @var KeyChainInterface[] */
            private $keyChains;

            public function __construct(array $keyChains = [])
            {
                foreach ($keyChains as $keyChain) {
                    $this->keyChains[$keyChain->getId()] = $keyChain;
                }
            }

            public function find(string $id): ?KeyChainInterface
            {
                return $this->keyChains[$id] ?? null;
            }

            /**
             * @return KeyChainInterface[]
             */
            public function findBySetName(string $setName): array
            {
                $result = [];

                foreach ($this->keyChains as $keyChain) {
                    if ($keyChain->getSetName() === $setName) {
                        $result[$keyChain->getId()] = $keyChain;
                    }
                }

                return $result;
            }
        };
    }
}
