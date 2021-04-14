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

namespace OAT\Library\Lti1p3Core\Tests\Integration\Flow\Message;

use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidator;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class PlatformOriginatingMessageFlowTest extends TestCase
{
    use DomainTestingTrait;
    use NetworkTestingTrait;

    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var RegistrationInterface */
    private $registration;

    /** @var bool */
    private $debug = false;

    protected function setUp(): void
    {
        $this->registrationRepository = $this->createTestRegistrationRepository();
        $this->registration = $this->registrationRepository->find('registrationIdentifier');
    }

    public function testPlatformOriginatingMessage():void
    {
        $ltiResourceLink = new LtiResourceLink('resourceLinkIdentifier');

        $message = (new LtiResourceLinkLaunchRequestBuilder())->buildLtiResourceLinkLaunchRequest(
            $ltiResourceLink,
            $this->registration,
            'loginHint',
            null,
            [
                'Learner'
            ],
            [
                'customClaim' => 'customValue',
                new ContextClaim('contextIdentifier')
            ]
        );

        $oidcInitiator = new OidcInitiator($this->registrationRepository);

        $oidcInitiationResult = $oidcInitiator->initiate($this->createServerRequest('GET', $message->toUrl()));

        $oidcAuthenticator = new OidcAuthenticator($this->registrationRepository, $this->createTestUserAuthenticator());

        $oidcAuthenticationResult = $oidcAuthenticator->authenticate($this->createServerRequest('GET', $oidcInitiationResult->toUrl()));

        $validator = new ToolLaunchValidator($this->registrationRepository, $this->createTestNonceRepository());

        $validationResult = $validator->validatePlatformOriginatingLaunch(
            $this->createServerRequest('POST', $oidcAuthenticationResult->getUrl(), $oidcAuthenticationResult->getParameters()->all())
        );

        $payload = $validationResult->getPayload();

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $payload->getVersion());
        $this->assertEquals(LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST, $payload->getMessageType());
        $this->assertEquals('userIdentifier', $payload->getUserIdentity()->getIdentifier());
        $this->assertEquals(['Learner'], $payload->getRoles());
        $this->assertEquals('customValue', $payload->getClaim('customClaim'));
        $this->assertEquals('contextIdentifier', $payload->getContext()->getIdentifier());
    }
}
