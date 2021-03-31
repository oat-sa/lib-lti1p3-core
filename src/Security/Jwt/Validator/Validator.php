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

namespace OAT\Library\Lti1p3Core\Security\Jwt\Validator;

use OAT\Library\Lti1p3Core\Security\Jwt\Configuration\ConfigurationFactory;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use Throwable;

class Validator implements ValidatorInterface
{
    /** @var ConfigurationFactory */
    private $factory;

    public function __construct(?ConfigurationFactory $factory = null)
    {
        $this->factory = $factory ?? new ConfigurationFactory();
    }

    public function validate(TokenInterface $token, KeyInterface $key): bool
    {
        try {
            $config = $this->factory->create(null, $key);
            $plainToken = $config->parser()->parse($token->toString());

            return $config->validator()->validate($plainToken, ...$config->validationConstraints());

        } catch (Throwable $exception) {
            return false;
        }
    }
}
