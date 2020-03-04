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

namespace OAT\Library\Lti1p3Core\Tool\Builder;

use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Launch\Message\AuthenticationRequestMessage;
use OAT\Library\Lti1p3Core\Launch\Message\LoginMessage;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\JwtBuilder;

class AuthenticationMessageBuilder
{
    /** @var JwtBuilder */
    private $jwtBuilder;

    public function __construct(JwtBuilder $jwtBuilder)
    {
        $this->jwtBuilder = $jwtBuilder;
    }

    public function getMessage(DeploymentInterface $deployment, NonceInterface $nonce, LoginMessage $loginMessage): AuthenticationRequestMessage
    {
        return (new AuthenticationRequestMessage())
            ->setResponseType('id_token')
            ->setRedirectUri($loginMessage->getTargetLinkUri())
            ->setResponseMode('form_post')
            ->setClientId($deployment->getClientId())
            ->setScope('openid')
            ->setState($this->jwtBuilder->generate($deployment, $loginMessage))
            ->setLoginHint($loginMessage->getLoginHint())
            ->setMessageHint($loginMessage->getLtiMessageHint())
            ->setPrompt('none')
            ->setNonce($nonce->getValue());
    }
}
