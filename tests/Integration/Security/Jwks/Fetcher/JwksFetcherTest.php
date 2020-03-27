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

use GuzzleHttp\ClientInterface;
use Lcobucci\JWT\Signer\Key;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JwksFetcherTest extends TestCase
{
    use SecurityTestingTrait;
    use NetworkTestingTrait;

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
        $this->clientMock = $this->createMock(ClientInterface::class);

        $this->subject = new JwksFetcher($this->clientMock);

        $this->keyChain = $this->createTestKeyChain();

        $this->exporter = new JwksExporter(new KeyChainRepository([$this->keyChain]));
    }

    public function testItCanFetchAJwksExposedKey(): void
    {
        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(
                $this->createResponse(json_encode($this->exporter->export($this->keyChain->getKeySetName())))
            );

        $key = $this->subject->fetchKey('http://test.com', $this->keyChain->getIdentifier());

        $this->assertInstanceOf(Key::class, $key);

        $expectedDetails = openssl_pkey_get_details(
            openssl_pkey_get_public($this->keyChain->getPublicKey()->getContent())
        );

        $jwksDetails = openssl_pkey_get_details(
            openssl_pkey_get_public($key->getContent())
        );

        $this->assertEquals($expectedDetails, $jwksDetails);
    }

    public function testItThrowAnLtiExceptionOnNoKeyFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Could not find key id invalid from url http://test.com');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn(
                $this->createResponse(json_encode($this->exporter->export($this->keyChain->getKeySetName())))
            );

        $this->subject->fetchKey('http://test.com', 'invalid');
    }

    public function testItThrowAnLtiExceptionOnInvalidJwksJsonResponse(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Error during JWK fetching for url http://test.com: json_decode error: Syntax error');

        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://test.com', ['headers' => ['Accept' => 'application/json']])
            ->willReturn($this->createResponse('invalid'));

        $this->subject->fetchKey('http://test.com', $this->keyChain->getIdentifier());
    }
}
