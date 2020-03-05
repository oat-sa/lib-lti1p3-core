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

namespace OAT\Library\Lti1p3Core\Security\Oidc;

/**
 * @see https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request
 */
class AuthenticationRequestParameters
{
    public const SCOPE = 'openid';
    public const RESPONSE_TYPE = 'id_token';
    public const RESPONSE_MODE = 'form_post';
    public const PROMPT = 'none';

    /** @var string */
    private $redirectUri;

    /** @var string */
    private $clientId;

    /** @var string */
    private $loginHint;

    /** @var string */
    private $nonce;

    /** @var string|null */
    private $state;

    /** @var string|null */
    private $ltiMessageHint;

    public function __construct(
        string $redirectUri,
        string $clientId,
        string $loginHint,
        string $nonce,
        string $state = null,
        string $ltiMessageHint = null
    ) {
        $this->redirectUri = $redirectUri;
        $this->clientId = $clientId;
        $this->loginHint = $loginHint;
        $this->nonce = $nonce;
        $this->state = $state;
        $this->ltiMessageHint = $ltiMessageHint;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getLoginHint(): string
    {
        return $this->loginHint;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getLtiMessageHint(): ?string
    {
        return $this->ltiMessageHint;
    }

    public function getScope(): string
    {
        return static::SCOPE;
    }

    public function getResponseType(): string
    {
        return static::RESPONSE_TYPE;
    }

    public function getResponseMode(): string
    {
        return static::RESPONSE_MODE;
    }

    public function getPrompt(): string
    {
        return static::PROMPT;
    }
}
