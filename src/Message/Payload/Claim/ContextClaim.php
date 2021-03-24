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

namespace OAT\Library\Lti1p3Core\Message\Payload\Claim;

use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#context-claim-0
 */
class ContextClaim implements MessagePayloadClaimInterface
{
    public const TYPE_COURSE_TEMPLATE = 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate';
    public const TYPE_COURSE_OFFERING = 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering';
    public const TYPE_COURSE_SECTION = 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection';
    public const TYPE_GROUP = 'http://purl.imsglobal.org/vocab/lis/v2/course#Group';

    /** @var string */
    private $id;

    /** @var string[] */
    private $types;

    /** @var string|null */
    private $label;

    /** @var string|null */
    private $title;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT;
    }

    public function __construct(string $id, array $types = [], ?string $label = null, ?string $title = null)
    {
        $this->id = $id;
        $this->types = $types;
        $this->label = $label;
        $this->title = $title;
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function normalize(): array
    {
        return array_filter([
            'id' => $this->id,
            'type' => $this->types,
            'label' => $this->label,
            'title' => $this->title,
        ]);
    }

    public static function denormalize(array $claimData): ContextClaim
    {
        return new self(
            $claimData['id'],
            $claimData['type'] ?? [],
            $claimData['label'] ?? null,
            $claimData['title'] ?? null
        );
    }
}
