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

namespace OAT\Library\Lti1p3Core\Security\OAuth2\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;

class Client implements ClientEntityInterface
{
    use ClientTrait;

    /** @var RegistrationInterface */
    private $registration;

    public function __construct(RegistrationInterface $registration)
    {
        $this->registration = $registration;
        $this->name = $this->registration->getTool()->getName();
        $this->redirectUri = $this->registration->getTool()->getLaunchUrl();
        $this->isConfidential = true;
    }

    public function getIdentifier(): string
    {
        return $this->registration->getClientId();
    }
}
