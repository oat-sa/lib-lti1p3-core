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

namespace OAT\Library\Lti1p3Core\Service\Server\Validator;

use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\AccessTokenRequestValidator;
use OAT\Library\Lti1p3Core\Service\Server\Validator\Result\ServiceServerRequestValidationResult;
use Psr\Http\Message\ServerRequestInterface;

class ServiceServerRequestAccessTokenValidator implements ServiceServerRequestValidatorInterface
{
    /** @var AccessTokenRequestValidator */
    protected $validator;

    /** @var string[] */
    private $allowedScopes;

    public function __construct(AccessTokenRequestValidator $validator, array $allowedScopes = [])
    {
        $this->validator = $validator;
        $this->allowedScopes = $allowedScopes;
    }

    public function validate(ServerRequestInterface $request): ServiceServerRequestValidationResult
    {
        $result = new ServiceServerRequestValidationResult();

        $accessTokenValidationResult = $this->validator->validate($request, $this->allowedScopes);

        if ($accessTokenValidationResult->hasError()) {
            $result
                ->setErrorHttpStatusCode(401)
                ->setError($accessTokenValidationResult->getError());
        }

        $result->setSuccesses($accessTokenValidationResult->getSuccesses());

        return $result;
    }
}
