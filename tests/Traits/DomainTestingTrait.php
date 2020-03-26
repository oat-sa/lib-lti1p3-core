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

namespace OAT\Library\Lti1p3Core\Tests\Traits;

use OAT\Library\Lti1p3Core\Deployment\Deployment;
use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tool\Tool;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

trait DomainTestingTrait
{
    use SecurityTestingTrait;

    private function createTestUserIdentity(
        string $identifier = 'userIdentifier',
        string $name = 'userName',
        string $email = 'userEmail',
        string $givenName = 'userGivenName',
        string $familyName = 'userFamilyName',
        string $middleName = 'userMiddleName',
        string $locale = 'userLocale',
        string $picture = 'userPicture'
    ): UserIdentity {
        return new UserIdentity($identifier, $name, $email, $givenName, $familyName, $middleName, $locale, $picture);
    }

    private function createTestPlatform(
        string $identifier = 'platformIdentifier',
        string $name = 'platformName',
        string $audience = 'platformAudience',
        string $oidcAuthenticationUrl = 'http://platform.com/oidc-auth',
        string $oauth2AccessTokenUrl = 'http://platform.com/access-token'
    ): Platform {
        return new Platform($identifier, $name, $audience, $oidcAuthenticationUrl, $oauth2AccessTokenUrl);
    }

    private function createTestTool(
        string $identifier = 'toolIdentifier',
        string $name = 'toolName',
        string $oidcLoginInitiationUrl = 'http://tool.com/oidc-init',
        string $launchUrl = 'http://tool.com/launch',
        string $deepLaunchUrl = 'http://tool.com/deep-launch'
    ): Tool {
        return new Tool($identifier, $name, $oidcLoginInitiationUrl, $launchUrl, $deepLaunchUrl);
    }

    private function createTestResourceLink(
        string $identifier = 'resourceLinkIdentifier',
        string $url = 'http://tool.com/resource-link',
        string $title = 'resourceLinkTitle',
        string $description = 'resourceLinkDescription'
    ): ResourceLink {
        return new ResourceLink($identifier, $url, $title, $description);
    }

    private function createTestDeployment(
        string $identifier = 'deploymentIdentifier',
        string $clientId = 'deploymentClientId',
        PlatformInterface $platform = null,
        ToolInterface $tool = null,
        KeyChainInterface $platformKeyChain = null,
        KeyChainInterface $toolKeyChain = null,
        string $platformJwksUrl = null,
        string $toolJwksUrl = null
    ): Deployment {
        return new Deployment(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            $platformKeyChain ?? $this->createTestKeyChain('platformKeyChain'),
            $toolKeyChain ?? $this->createTestKeyChain('toolKeyChain'),
            $platformJwksUrl,
            $toolJwksUrl
        );
    }

    private function createTestDeploymentWithJwksPlatform(
        string $identifier = 'deploymentIdentifier',
        string $clientId = 'deploymentClientId',
        string $platformJwksUrl = 'http://platform.com/jwks'
    ): Deployment {
        return new Deployment(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            null,
            $toolKeyChain ?? $this->createTestKeyChain('toolKeyChain'),
            $platformJwksUrl,
            null
        );
    }

    private function createTestDeploymentWithoutToolKeyChain(
        string $identifier = 'deploymentIdentifier',
        string $clientId = 'deploymentClientId',
        KeyChainInterface $platformKeyChain = null
    ): Deployment {
        return new Deployment(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            $platformKeyChain ?? $this->createTestKeyChain('platformKeyChain'),
            null,
            null,
            null
        );
    }

    private function createTestDeploymentRepository(array $deployments = []): DeploymentRepositoryInterface
    {
        $deployments = !empty($deployments)
            ? $deployments
            : [$this->createTestDeployment()];

        return new class ($deployments) implements DeploymentRepositoryInterface
        {
            /** @var DeploymentInterface[] */
            private $deployments;

            /** @param DeploymentInterface[] $deployments */
            public function __construct(array $deployments)
            {
                foreach ($deployments as $deployment) {
                    $this->deployments[$deployment->getIdentifier()] = $deployment;
                }
            }

            public function find(string $identifier): ?DeploymentInterface
            {
                return $this->deployments[$identifier] ?? null;
            }

            public function findByIssuer(string $issuer, string $clientId = null): ?DeploymentInterface
            {
                foreach ($this->deployments as $deployment) {
                    if ($deployment->getPlatform()->getAudience() === $issuer) {
                        if (null !== $clientId) {
                            if ($deployment->getClientId() === $clientId) {
                                return $deployment;
                            }
                        } else {
                            return $deployment;
                        }
                    }
                }

                return null;
            }
        };
    }
}
