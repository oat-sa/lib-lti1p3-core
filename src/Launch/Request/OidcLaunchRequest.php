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

namespace OAT\Library\Lti1p3Core\Launch\Request;

use OAT\Library\Lti1p3Core\Launch\AbstractLaunchRequest;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
 */
class OidcLaunchRequest extends AbstractLaunchRequest
{
    public function __construct(
        string $url,
        string $issuer,
        string $loginHint,
        string $targetLinkUri,
        string $ltiMessageHint = null,
        string $ltiDeploymentId = null,
        string $clientId = null,
        array $parameters = []
    ) {
        parent::__construct($url, array_merge($parameters, [
            'iss' => $issuer,
            'login_hint' => $loginHint,
            'target_link_uri' => $targetLinkUri,
            'lti_message_hint' => $ltiMessageHint,
            'lti_deployment_id' => $ltiDeploymentId,
            'client_id' => $clientId,
        ]));
    }

    public function getIssuer(): string
    {
        return $this->getParameter('iss');
    }

    public function getLoginHint(): string
    {
        return $this->getParameter('login_hint');
    }

    public function getTargetLinkUri(): string
    {
        return $this->getParameter('target_link_uri');
    }

    public function getLtiMessageHint(): ?string
    {
        return $this->getParameter('lti_message_hint');
    }

    public function getLtiDeploymentId(): ?string
    {
        return $this->getParameter('lti_deployment_id');
    }

    public function getClientId(): ?string
    {
        return $this->getParameter('client_id');
    }
}
