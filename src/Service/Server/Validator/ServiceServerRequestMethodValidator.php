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

use OAT\Library\Lti1p3Core\Service\Server\Validator\Result\ServiceServerRequestValidationResult;
use Psr\Http\Message\ServerRequestInterface;

class ServiceServerRequestMethodValidator implements ServiceServerRequestValidatorInterface
{
    /** @var string[] */
    private $allowedHttpMethods;

    public function __construct(array $allowedHttpMethods = [])
    {
        $this->allowedHttpMethods = array_map(
            static function (string $allowedHttpMethod): string {
                return strtolower($allowedHttpMethod);
            },
            $allowedHttpMethods
        );
    }

    public function validate(ServerRequestInterface $request): ServiceServerRequestValidationResult
    {
        $result = new ServiceServerRequestValidationResult();

        if (!in_array(strtolower($request->getMethod()), $this->allowedHttpMethods)) {
            $message = sprintf('Not acceptable request HTTP method, accepts: %s', implode(', ', $this->allowedHttpMethods));

            $result
                ->setErrorHttpStatusCode(405)
                ->setError($message);
        }

        $result->addSuccess('Acceptable request HTTP method');

        return $result;
    }
}
