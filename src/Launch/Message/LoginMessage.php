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

use OAT\Library\Lti1p3Core\Configuration\Message\LoginMessageConfiguration;

class LoginMessage extends LoginMessageConfiguration implements MessageInterface
{
    /** @var string */
    private $issuer;

    /** @var string */
    private $loginHint;

    /** @var string */
    private $targetLinkUri;

    /** @var string|null */
    private $ltiMessageHint;

    /** @var string|null */
    private $ltiDeploymentId;

    /** @var string|null */
    private $clientId;

    public function __construct(
        string $issuer,
        string $loginHint,
        string $targetLinkUri,
        string $ltiMessageHint = null,
        string $ltiDeploymentId = null,
        string $clientId = null
    ) {
        $this->issuer = $issuer;
        $this->loginHint = $loginHint;
        $this->targetLinkUri = $targetLinkUri;
        $this->ltiMessageHint = $ltiMessageHint;
        $this->ltiDeploymentId = $ltiDeploymentId;
        $this->clientId = $clientId;
    }

    public function export(): array
    {
        return array_filter(
            [
                self::ATTRIBUTE_ISSUER => $this->getIssuer(),
                self::ATTRIBUTE_LOGIN_HINT => $this->getLoginHint(),
                self::ATTRIBUTE_TARGET_LINK_URI => $this->getTargetLinkUri(),
                self::ATTRIBUTE_LTI_MESSAGE_HINT => $this->getLtiMessageHint(),
                self::ATTRIBUTE_LTI_DEPLOYMENT_ID => $this->getLtiDeploymentId(),
                self::ATTRIBUTE_CLIENT_ID => $this->getClientId()
            ]
        );
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getLoginHint(): string
    {
        return $this->loginHint;
    }

    public function getTargetLinkUri(): string
    {
        return $this->targetLinkUri;
    }

    public function getLtiMessageHint(): ?string
    {
        return $this->ltiMessageHint;
    }

    public function getLtiDeploymentId(): ?string
    {
        return $this->ltiDeploymentId;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }
}
