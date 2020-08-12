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

use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
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
        string $picture = 'userPicture',
        array $additionalProperties = []
    ): UserIdentity {
        return new UserIdentity($identifier, $name, $email, $givenName, $familyName, $middleName, $locale, $picture, $additionalProperties);
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
        string $audience = 'platformAudience',
        string $oidcLoginInitiationUrl = 'http://tool.com/oidc-init',
        string $launchUrl = 'http://tool.com/launch',
        string $deepLaunchUrl = 'http://tool.com/deep-launch'
    ): Tool {
        return new Tool($identifier, $name, $audience, $oidcLoginInitiationUrl, $launchUrl, $deepLaunchUrl);
    }

    private function createTestResourceLink(
        string $identifier = 'resourceLinkIdentifier',
        string $url = 'http://tool.com/resource-link',
        string $title = 'resourceLinkTitle',
        string $description = 'resourceLinkDescription'
    ): ResourceLink {
        return new ResourceLink($identifier, $url, $title, $description);
    }

    private function createTestRegistration(
        string $identifier = 'registrationIdentifier',
        string $clientId = 'registrationClientId',
        PlatformInterface $platform = null,
        ToolInterface $tool = null,
        array $deploymentIds = ['deploymentIdentifier'],
        KeyChainInterface $platformKeyChain = null,
        KeyChainInterface $toolKeyChain = null,
        string $platformJwksUrl = null,
        string $toolJwksUrl = null
    ): Registration {
        return new Registration(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            $deploymentIds,
            $platformKeyChain ?? $this->createTestKeyChain('platformKeyChain'),
            $toolKeyChain ?? $this->createTestKeyChain('toolKeyChain'),
            $platformJwksUrl,
            $toolJwksUrl
        );
    }

    private function createTestRegistrationWithJwksPlatform(
        string $identifier = 'registrationIdentifier',
        string $clientId = 'registrationClientId',
        string $platformJwksUrl = 'http://platform.com/jwks'
    ): Registration {
        return new Registration(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            ['deploymentIdentifier'],
            null,
            $toolKeyChain ?? $this->createTestKeyChain('toolKeyChain'),
            $platformJwksUrl,
            null
        );
    }

    private function createTestRegistrationWithoutToolKeyChain(
        string $identifier = 'registrationIdentifier',
        string $clientId = 'registrationClientId',
        KeyChainInterface $platformKeyChain = null,
        string $platformJwksUrl = 'http://platform.com/jwks',
        string $toolJwksUrl = 'http://tool.com/jwks'
    ): Registration {
        return new Registration(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            ['deploymentIdentifier'],
            $platformKeyChain ?? $this->createTestKeyChain('platformKeyChain'),
            null,
            $platformJwksUrl,
            $toolJwksUrl
        );
    }

    private function createTestRegistrationWithoutPlatformKeyChain(
        string $identifier = 'registrationIdentifier',
        string $clientId = 'registrationClientId'
    ): Registration {
        return new Registration(
            $identifier,
            $clientId,
            $platform ?? $this->createTestPlatform(),
            $tool ?? $this->createTestTool(),
            ['deploymentIdentifier']
        );
    }

    private function createTestRegistrationRepository(array $registrations = []): RegistrationRepositoryInterface
    {
        $registrations = !empty($registrations)
            ? $registrations
            : [$this->createTestRegistration()];

        return new class ($registrations) implements RegistrationRepositoryInterface
        {
            /** @var RegistrationInterface[] */
            private $registrations;

            /** @param RegistrationInterface[] $registrations */
            public function __construct(array $registrations)
            {
                foreach ($registrations as $registration) {
                    $this->registrations[$registration->getIdentifier()] = $registration;
                }
            }

            public function find(string $identifier): ?RegistrationInterface
            {
                return $this->registrations[$identifier] ?? null;
            }

            public function findAll(): array
            {
                return $this->registrations;
            }

            public function findByClientId(string $clientId): ?RegistrationInterface
            {
                foreach ($this->registrations as $registration) {
                    if ($registration->getClientId() === $clientId) {
                        return $registration;
                    }
                }

                return null;
            }

            public function findByPlatformIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
            {
                foreach ($this->registrations as $registration) {
                    if ($registration->getPlatform()->getAudience() === $issuer) {
                        if (null !== $clientId) {
                            if ($registration->getClientId() === $clientId) {
                                return $registration;
                            }
                        } else {
                            return $registration;
                        }
                    }
                }

                return null;
            }

            public function findByToolIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
            {
                foreach ($this->registrations as $registration) {
                    if ($registration->getTool()->getAudience() === $issuer) {
                        if (null !== $clientId) {
                            if ($registration->getClientId() === $clientId) {
                                return $registration;
                            }
                        } else {
                            return $registration;
                        }
                    }
                }

                return null;
            }
        };
    }
}
