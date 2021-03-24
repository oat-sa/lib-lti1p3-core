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

namespace OAT\Library\Lti1p3Core\Service\Server\Handler;

use Http\Message\ResponseFactory;
use Nyholm\Psr7\Factory\HttplugFactory;
use OAT\Library\Lti1p3Core\Service\Server\Validator\Collection\ServiceServerRequestValidatorCollection;
use OAT\Library\Lti1p3Core\Service\Server\Validator\Collection\ServiceServerRequestValidatorCollectionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

abstract class AbstractServiceServerRequestHandler implements RequestHandlerInterface
{
    /** @var ServiceServerRequestValidatorCollectionInterface */
    private $validators;

    /** @var ResponseFactory */
    protected $factory;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        ?ServiceServerRequestValidatorCollectionInterface $validators = null,
        ?ResponseFactory $factory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->validators = $validators ?? new ServiceServerRequestValidatorCollection();
        $this->factory = $factory ?? new HttplugFactory();
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->validators->all() as $validator) {

            $validationResult = $validator->validate($request);

            if ($validationResult->hasError()) {

                $this->logger->error($validationResult->getError());

                return $this->factory->createResponse(
                    $validationResult->getErrorHttpStatusCode() ?? 500,
                    null,
                    [],
                    $validationResult->getError()
                );
            }

            foreach ($validationResult->getSuccesses() as $success) {
                $this->logger->debug($success);
            }
        }

        try {
            return $this->handleRequest($request);
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());

            return $this->factory->createResponse(
                500,
                null,
                [],
                sprintf('Internal %s service error', $this->getName())
            );
        }
    }

    protected abstract function handleRequest(ServerRequestInterface $request): ResponseInterface;

    protected abstract function getName(): string;
}
