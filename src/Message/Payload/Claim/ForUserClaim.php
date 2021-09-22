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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\Lti1p3Core\Message\Payload\Claim;

use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;

/**
 * @see https://www.imsglobal.org/spec/lti-sr/v1p0#for-user-claim
 */
class ForUserClaim implements MessagePayloadClaimInterface
{
    /** @var string */
    private $identifier;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $givenName;

    /** @var string|null */
    private $familyName;

    /** @var string|null */
    private $email;

    /** @var string|null */
    private $personSourcedId;

    /** @var string[] */
    private $roles;

    public function __construct(
        string $identifier,
        ?string $name = null,
        ?string $givenName = null,
        ?string $familyName = null,
        ?string $email = null,
        ?string $personSourcedId = null,
        array $roles = []
    ) {
        $this->identifier = $identifier;
        $this->personSourcedId = $personSourcedId;
        $this->name = $name;
        $this->givenName = $givenName;
        $this->familyName = $familyName;
        $this->email = $email;
        $this->roles = $roles;
    }

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_FOR_USER;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPersonSourcedId(): ?string
    {
        return $this->personSourcedId;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function normalize(): array
    {
        return array_filter([
            'user_id' => $this->identifier,
            'name' => $this->name,
            'given_name' => $this->givenName,
            'family_name' => $this->familyName,
            'email' => $this->email,
            'person_sourcedid' => $this->personSourcedId,
            'roles' => $this->roles,
        ]);
    }

    public static function denormalize(array $claimData): ForUserClaim
    {
        return new self(
            $claimData['user_id'],
            $claimData['name'] ?? null,
            $claimData['given_name'] ?? null,
            $claimData['family_name'] ?? null,
            $claimData['email'] ?? null,
            $claimData['person_sourcedid'] ?? null,
            $claimData['roles'] ?? []
        );
    }
}
