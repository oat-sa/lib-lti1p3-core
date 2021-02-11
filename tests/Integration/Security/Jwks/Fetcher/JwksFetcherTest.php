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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Security\Jwks\Fetcher;

use Cache\Adapter\PHPArray\ArrayCachePool;
use GuzzleHttp\ClientInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JwksFetcherTest extends TestCase
{
    use SecurityTestingTrait;
    use NetworkTestingTrait;

    /** @var ArrayCachePool */
    private $cache;

    /** @var ClientInterface|MockObject */
    private $clientMock;

    /** @var JwksFetcher */
    private $subject;

    /** @var JwksExporter */
    private $exporter;

    /** @var KeyChainInterface */
    private $keyChain;

    protected function setUp(): void
    {
        $this->cache = new ArrayCachePool();

        $this->clientMock = $this->createMock(ClientInterface::class);

        $this->subject = new JwksFetcher($this->cache, $this->clientMock);

        $this->keyChain = $this->createTestKeyChain();

        $this->exporter = new JwksExporter(new KeyChainRepository([$this->keyChain]));
    }

    public function testItCanFetchAJwksFromJwksUrlKeyAndSaveItInCache(): void
    {
        $jwksData = $this->exporter->export($this->keyChain->getKeySetName());

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(
                $this->createResponse(json_encode($jwksData))
            );

        $key = $this->subject->fetchKey('http://test.com', $this->keyChain->getIdentifier());

        $this->assertInstanceOf(KeyInterface::class, $key);

        $this->assertEquals(current($jwksData['keys']), $key->getContent());

        $this->assertTrue($this->cache->has('lti1p3-jwks-' . base64_encode('http://test.com')));
    }

    public function testItCanFetchAJwksKeyFromExistingCache(): void
    {
        $jwksData = $this->exporter->export($this->keyChain->getKeySetName());

        $this->cache->set(
            'lti1p3-jwks-' . base64_encode('http://test.com'),
            $jwksData
        );

        $this->clientMock->expects($this->never())->method('request');

        $key = $this->subject->fetchKey('http://test.com', $this->keyChain->getIdentifier());

        $this->assertInstanceOf(KeyInterface::class, $key);

        $this->assertEquals(current($jwksData['keys']), $key->getContent());
    }

    public function testItCanFetchAJwksKeyFromUrlIfCacheIsMissingKey(): void
    {
        $jwksData = $this->exporter->export($this->keyChain->getKeySetName());

        $this->cache->set(
            'lti1p3-jwks-' . base64_encode('http://test.com'),
            ['keys' => []]
        );

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(
                $this->createResponse(json_encode($jwksData))
            );

        $key = $this->subject->fetchKey('http://test.com', $this->keyChain->getIdentifier());

        $this->assertInstanceOf(KeyInterface::class, $key);

        $this->assertEquals(current($jwksData['keys']), $key->getContent());
    }

    public function testItThrowAnLtiExceptionOnMissingKeyFromBothCacheAndJwksUrl(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Could not find key id invalid from cache or url http://test.com');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn($this->createResponse(json_encode([])));

        $this->subject->fetchKey('http://test.com', 'invalid');
    }

    public function testItThrowAnLtiExceptionOnKeyIdNotFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Could not find key id invalid from cache or url http://test.com');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(
                $this->createResponse(json_encode($this->exporter->export($this->keyChain->getKeySetName())))
            );

        $this->subject->fetchKey('http://test.com', 'invalid');
    }

    public function testItThrowAnLtiExceptionOnInvalidJwksUrlResponse(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Cannot fetch JWKS data from url http://test.com: json_decode error: Syntax error');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn($this->createResponse('invalid'));

        $this->subject->fetchKey('http://test.com', $this->keyChain->getIdentifier());
    }
}
