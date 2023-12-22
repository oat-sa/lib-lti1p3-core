<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Security\Oidc\Server;

use Nyholm\Psr7\Response;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request
 */
class OidcInitiationRequestHandler implements RequestHandlerInterface
{
    /** @var OidcInitiator */
    private $initiator;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        OidcInitiator $initiator,
        ?LoggerInterface $logger = null
    ) {
        $this->initiator = $initiator;
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $message = $this->initiator->initiate($request);

            return new Response(302, ['Location' => $message->toUrl()]);

        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());

            return new Response(500, [], 'Internal OIDC initiation server error');
        }
    }
}
