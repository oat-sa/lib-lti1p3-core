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

namespace OAT\Library\Lti1p3Core\Service\Server;

use Http\Message\ResponseFactory;
use Nyholm\Psr7\Factory\HttplugFactory;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidatorInterface;
use OAT\Library\Lti1p3Core\Service\Server\Handler\LtiServiceServerRequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class LtiServiceServer implements RequestHandlerInterface
{
    /** @var RequestAccessTokenValidatorInterface */
    private $validator;

    /** @var LtiServiceServerRequestHandlerInterface */
    private $handler;

    /** @var ResponseFactory */
    private $factory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RequestAccessTokenValidatorInterface $validator,
        LtiServiceServerRequestHandlerInterface $handler,
        ?ResponseFactory $factory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->validator = $validator;
        $this->handler = $handler;
        $this->factory = $factory ?? new HttplugFactory();
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $allowedMethods = array_map('strtolower', $this->handler->getAllowedMethods());

        if (!empty($allowedMethods) && !in_array(strtolower($request->getMethod()), $allowedMethods)) {
            $message = sprintf('Not acceptable request method, accepts: [%s]', implode(', ', $allowedMethods));
            $this->logger->error($message);

            return $this->factory->createResponse(405, null, [], $message);
        }

        $allowedContentType = $this->handler->getAllowedContentType();

        $contentTypeHeader = 'get' === strtolower($request->getMethod()) ? 'Accept' : 'Content-Type';

        if (!empty($allowedContentType) && false === strpos($request->getHeaderLine($contentTypeHeader), $allowedContentType)) {
            $message = sprintf('Not acceptable request content type, accepts: %s', $allowedContentType);
            $this->logger->error($message);

            return $this->factory->createResponse(406, null, [], $message);
        }

        $validationResult = $this->validator->validate($request, $this->handler->getAllowedScopes());

        if ($validationResult->hasError()) {
            $this->logger->error($validationResult->getError());

            return $this->factory->createResponse(401, null, [], $validationResult->getError());
        }

        try {
            $response = $this->handler->handleValidatedServiceRequest($validationResult, $request);

            $this->logger->info(sprintf('%s service success', $this->handler->getServiceName()));

            return $response;

        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());

            return $this->factory->createResponse(
                500,
                null,
                [],
                sprintf('Internal %s service error', $this->handler->getServiceName())
            );
        }
    }
}
