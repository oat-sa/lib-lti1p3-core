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

use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidator;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use OAT\Library\Lti1p3Core\Tests\Traits\NetworkTestingTrait;
use PHPUnit\Framework\TestCase;

class ToolOriginatingMessageFlowTest extends TestCase
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

    public function testToolOriginatingMessage():void
    {
        $deepLinkingData = $this->buildJwt([], [], $this->registration->getToolKeyChain()->getPrivateKey())->toString();

        $message = (new ToolOriginatingLaunchBuilder())->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            'http://platform.com/service',
            null,
            [
                LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => $deepLinkingData,
                'customClaim' => 'customValue'
            ]
        );

        $validator = new PlatformLaunchValidator($this->registrationRepository, $this->createTestNonceRepository());

        $validationResult = $validator->validateToolOriginatingLaunch(
            $this->createServerRequest('POST', $message->getUrl(), $message->getParameters()->all())
        );

        $payload = $validationResult->getPayload();

        $this->assertEquals(LtiMessageInterface::LTI_VERSION, $payload->getVersion());
        $this->assertEquals(LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE, $payload->getMessageType());
        $this->assertEquals($deepLinkingData, $payload->getDeepLinkingData());
        $this->assertEquals('customValue', $payload->getClaim('customClaim'));
    }
}
