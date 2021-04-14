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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Tests\Unit\Service\Server;

use Carbon\Carbon;
use Exception;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidator;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResultInterface;
use OAT\Library\Lti1p3Core\Service\Server\Handler\LtiServiceServerRequestHandlerInterface;
use OAT\Library\Lti1p3Core\Service\Server\LtiServiceServer;
use OAT\Library\Lti1p3Core\Tests\Resource\Logger\TestLogger;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class LtiServiceServerTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var RequestAccessTokenValidator */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->validator = new RequestAccessTokenValidator($this->createTestRegistrationRepository());
        $this->logger = new TestLogger();
    }

    public function testServiceServerSuccess(): void
    {
        $registration = $this->createTestRegistration();

        $serviceRequest = $this->createServerRequest(
            'GET',
            'http://example.com',
            [],
            [
                'Accept' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->createTestClientAccessToken($registration, ['scope']))
            ]
        );

        $serviceResponse = $this->createResponse('Service success');

        $handler = $this->createTestRequestHandler(
            static function() use ($serviceResponse): ResponseInterface {
                return $serviceResponse;
            },
            'application/json',
            ['GET'],
            ['scope']
        );

        $subject = new LtiServiceServer($this->validator, $handler, null, $this->logger);

        $response = $subject->handle($serviceRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Service success', (string)$response->getBody());

        $this->assertTrue($this->logger->hasLog('info', 'test-service service success'));
    }

    public function testServiceServerFailureOnInvalidMethod(): void
    {
        $serviceRequest = $this->createServerRequest(
            'POST',
            'http://example.com'
        );

        $handler = $this->createTestRequestHandler(
            null,
            null,
            ['GET']
        );

        $subject = new LtiServiceServer($this->validator, $handler, null, $this->logger);

        $response = $subject->handle($serviceRequest);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('Not acceptable request method, accepts: [get]', (string)$response->getBody());

        $this->assertTrue($this->logger->hasLog('error', 'Not acceptable request method, accepts: [get]'));
    }

    public function testServiceServerFailureOnInvalidContentType(): void
    {
        $serviceRequest = $this->createServerRequest(
            'POST',
            'http://example.com',
            [],
            [
                'Accept' => 'text/xml'
            ]
        );

        $handler = $this->createTestRequestHandler(
            null,
            'application/json',
            ['POST']
        );

        $subject = new LtiServiceServer($this->validator, $handler, null, $this->logger);

        $response = $subject->handle($serviceRequest);

        $this->assertEquals(406, $response->getStatusCode());
        $this->assertEquals('Not acceptable request content type, accepts: application/json', (string)$response->getBody());

        $this->assertTrue($this->logger->hasLog('error', 'Not acceptable request content type, accepts: application/json'));
    }

    public function testServiceServerFailureOnInvalidAccessToken(): void
    {
        $registration = $this->createTestRegistration();

        $now = Carbon::now();
        Carbon::setTestNow($now->subHour());
        $token = $this->createTestClientAccessToken($registration, ['scope']);
        Carbon::setTestNow();

        $serviceRequest = $this->createServerRequest(
            'GET',
            'http://example.com',
            [],
            [
                'Accept' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $token)
            ]
        );

        $handler = $this->createTestRequestHandler(
            null,
            'application/json',
            ['GET'],
            ['scope']
        );

        $subject = new LtiServiceServer($this->validator, $handler, null, $this->logger);

        $response = $subject->handle($serviceRequest);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('JWT access token is invalid', (string)$response->getBody());

        $this->assertTrue($this->logger->hasLog('error', 'JWT access token is invalid'));
    }

    public function testServiceServerFailureOnFailingHandler(): void
    {
        $registration = $this->createTestRegistration();

        $serviceRequest = $this->createServerRequest(
            'GET',
            'http://example.com',
            [],
            [
                'Accept' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->createTestClientAccessToken($registration, ['scope']))
            ]
        );

        $handler = $this->createTestRequestHandler(
            static function() {
                throw new Exception('handler error');
            },
            'application/json',
            ['GET'],
            ['scope']
        );

        $subject = new LtiServiceServer($this->validator, $handler, null, $this->logger);

        $response = $subject->handle($serviceRequest);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal test-service service error', (string)$response->getBody());

        $this->assertTrue($this->logger->hasLog('error', 'handler error'));
    }

    private function createTestRequestHandler(
        ?callable $func,
        ?string $contentType,
        array $methods = [],
        array $scopes = []
    ): LtiServiceServerRequestHandlerInterface {
        return new class($func, $contentType, $methods, $scopes) implements LtiServiceServerRequestHandlerInterface
        {
            /** @var callable */
            private $func;

            /** @var string|null */
            private $contentType;

            /** @var string[] */
            private $methods;

            /** @var string[] */
            private $scopes;

            public function __construct(?callable $func, ?string $contentType, array $methods, array $scopes)
            {
                $this->func = $func;
                $this->contentType = $contentType;
                $this->methods = $methods;
                $this->scopes = $scopes;
            }

            public function getServiceName(): string
            {
                return 'test-service';
            }

            public function getAllowedContentType(): ?string
            {
                return $this->contentType;
            }

            public function getAllowedMethods(): array
            {
                return $this->methods;
            }

            public function getAllowedScopes(): array
            {
                return $this->scopes;
            }

            public function handleValidatedServiceRequest(
                RequestAccessTokenValidationResultInterface $validationResult,
                ServerRequestInterface $request,
                array $options = []
            ): ResponseInterface {
                return call_user_func($this->func, $validationResult, $request);
            }
        };
    }
}
