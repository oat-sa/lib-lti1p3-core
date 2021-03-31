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

namespace OAT\Library\Lti1p3Core\User;

use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#user-identity-claims-0
 */
class UserIdentity implements UserIdentityInterface
{
    /** @var string */
    private $identifier;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $email;

    /** @var string|null */
    private $givenName;

    /** @var string|null */
    private $familyName;

    /** @var string|null */
    private $middleName;

    /** @var string|null */
    private $locale;

    /** @var string|null */
    private $picture;

    /** @var CollectionInterface */
    private $additionalProperties;

    public function __construct(
        string $identifier,
        ?string $name = null,
        ?string $email = null,
        ?string $givenName = null,
        ?string $familyName = null,
        ?string $middleName = null,
        ?string $locale = null,
        ?string $picture = null,
        array $additionalProperties = []
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->email = $email;
        $this->givenName = $givenName;
        $this->familyName = $familyName;
        $this->middleName = $middleName;
        $this->locale = $locale;
        $this->picture = $picture;
        $this->additionalProperties = (new Collection())->add($additionalProperties);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function getAdditionalProperties(): CollectionInterface
    {
        return $this->additionalProperties;
    }

    public function normalize(): array
    {
        return array_filter(
            array_merge(
                $this->additionalProperties->all(),
                [
                    MessagePayloadInterface::CLAIM_SUB => $this->getIdentifier(),
                    MessagePayloadInterface::CLAIM_USER_NAME => $this->getName(),
                    MessagePayloadInterface::CLAIM_USER_EMAIL => $this->getEmail(),
                    MessagePayloadInterface::CLAIM_USER_GIVEN_NAME => $this->getGivenName(),
                    MessagePayloadInterface::CLAIM_USER_FAMILY_NAME => $this->getFamilyName(),
                    MessagePayloadInterface::CLAIM_USER_MIDDLE_NAME => $this->getMiddleName(),
                    MessagePayloadInterface::CLAIM_USER_LOCALE => $this->getLocale(),
                    MessagePayloadInterface::CLAIM_USER_PICTURE => $this->getPicture(),
                ]
            )
        );
    }
}
