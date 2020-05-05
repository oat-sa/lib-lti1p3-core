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

namespace OAT\Library\Lti1p3Core\Tests\Unit\Security\Jwks\Fetcher;

use Cache\Adapter\Common\CacheItem;
use CoderCat\JWKToPEM\JWKConverter;
use Exception;
use GuzzleHttp\ClientInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Tests\Resource\Logger\TestLogger;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LogLevel;

class JwksFetcherTest extends TestCase
{
    use SecurityTestingTrait;
    use NetworkTestingTrait;

    /** @var CacheItemPoolInterface|MockObject */
    private $cacheMock;

    /** @var ClientInterface|MockObject */
    private $clientMock;

    /** @var TestLogger */
    private $logger;

    /** @var JwksFetcher */
    private $subject;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheItemPoolInterface::class);

        $this->clientMock = $this->createMock(ClientInterface::class);

        $this->logger = new TestLogger();

        $this->subject = new JwksFetcher($this->cacheMock, $this->clientMock, new JWKConverter(), $this->logger);
    }

    public function testItLogCacheFetchAndSaveErrors(): void
    {
        $cacheKey = 'lti1p3-jwks-' . base64_encode('http://test.com');

        $keyChain = $this->createTestKeyChain();

        $exporter = new JwksExporter(new KeyChainRepository([$keyChain]));

        $this->cacheMock
            ->expects($this->at(0))
            ->method('getItem')
            ->with($cacheKey)
            ->willThrowException(new Exception('cache fetch error'));

        $this->cacheMock
            ->expects($this->at(1))
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn(new CacheItem($cacheKey));

        $this->cacheMock
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception('cache save error'));

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(
                $this->createResponse(json_encode($exporter->export($keyChain->getKeySetName())))
            );

        $this->subject->fetchKey('http://test.com', $keyChain->getIdentifier());

        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Cannot fetch JWKS data from cache: cache fetch error'));
        $this->assertTrue($this->logger->hasLog(LogLevel::ERROR, 'Cannot save JWKS data in cache: cache save error'));
    }
}
