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

namespace OAT\Library\Lti1p3Core\Launch\Message;

use OAT\Library\Lti1p3Core\Configuration\Message\AuthenticationMessageConfiguration;

class AuthenticationRequestMessage extends AuthenticationMessageConfiguration implements MessageInterface
{
    /** @var string */
    private $responseType;

    /** @var string */
    private $redirectUri;

    /** @var string */
    private $responseMode;

    /** @var string */
    private $clientId;

    /** @var string */
    private $scope;

    /** @var string */
    private $state;

    /** @var string */
    private $loginHint;

    /** @var string */
    private $messageHint;

    /** @var string */
    private $prompt;

    /** @var string */
    private $nonce;

    public function export(): array
    {
        return array_filter(
            [
                self::ATTRIBUTE_RESPONSE_TYPE => $this->getResponseType(),
                self::ATTRIBUTE_REDIRECT_URI => $this->getRedirectUri(),
                self::ATTRIBUTE_RESPONSE_MODE => $this->getResponseMode(),
                self::ATTRIBUTE_CLIENT_ID => $this->getClientId(),
                self::ATTRIBUTE_SCOPE => $this->getScope(),
                self::ATTRIBUTE_STATE => $this->getState(),
                self::ATTRIBUTE_LOGIN_HINT => $this->getLoginHint(),
                self::ATTRIBUTE_MESSAGE_HINT => $this->getMessageHint(),
                self::ATTRIBUTE_PROMPT => $this->getPrompt(),
                self::ATTRIBUTE_NONCE => $this->getNonce()
            ]
        );
    }

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * @param string $responseType
     * @return AuthenticationRequestMessage
     */
    public function setResponseType(string $responseType): AuthenticationRequestMessage
    {
        $this->responseType = $responseType;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     * @return AuthenticationRequestMessage
     */
    public function setRedirectUri(string $redirectUri): AuthenticationRequestMessage
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseMode(): string
    {
        return $this->responseMode;
    }

    /**
     * @param string $responseMode
     * @return AuthenticationRequestMessage
     */
    public function setResponseMode(string $responseMode): AuthenticationRequestMessage
    {
        $this->responseMode = $responseMode;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return AuthenticationRequestMessage
     */
    public function setClientId(string $clientId): AuthenticationRequestMessage
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     * @return AuthenticationRequestMessage
     */
    public function setScope(string $scope): AuthenticationRequestMessage
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return AuthenticationRequestMessage
     */
    public function setState(string $state): AuthenticationRequestMessage
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginHint(): string
    {
        return $this->loginHint;
    }

    /**
     * @param string $loginHint
     * @return AuthenticationRequestMessage
     */
    public function setLoginHint(string $loginHint): AuthenticationRequestMessage
    {
        $this->loginHint = $loginHint;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessageHint(): string
    {
        return $this->messageHint;
    }

    /**
     * @param string $messageHint
     * @return AuthenticationRequestMessage
     */
    public function setMessageHint(string $messageHint): AuthenticationRequestMessage
    {
        $this->messageHint = $messageHint;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * @param string $prompt
     * @return AuthenticationRequestMessage
     */
    public function setPrompt(string $prompt): AuthenticationRequestMessage
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * @param string $nonce
     * @return AuthenticationRequestMessage
     */
    public function setNonce(string $nonce): AuthenticationRequestMessage
    {
        $this->nonce = $nonce;
        return $this;
    }
}
