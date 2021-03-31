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
 * @see http://www.imsglobal.org/spec/lti/v1p3/#learning-information-services-lis-claim-0
 */
class LisClaim implements MessagePayloadClaimInterface
{
    /** @var string|null */
    private $courseOfferingSourcedId;

    /** @var string|null */
    private $courseSectionSourcedId;

    /** @var string|null */
    private $outcomeServiceUrl;

    /** @var string|null */
    private $personSourcedId;

    /** @var string|null */
    private $resultSourcedId;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_LIS;
    }

    public function __construct(
        ?string $courseOfferingSourcedId = null,
        ?string $courseSectionSourcedId = null,
        ?string $outcomeServiceUrl = null,
        ?string $personSourcedId = null,
        ?string $resultSourcedId = null
    ) {
        $this->courseOfferingSourcedId = $courseOfferingSourcedId;
        $this->courseSectionSourcedId = $courseSectionSourcedId;
        $this->outcomeServiceUrl = $outcomeServiceUrl;
        $this->personSourcedId = $personSourcedId;
        $this->resultSourcedId = $resultSourcedId;
    }

    public function getCourseOfferingSourcedId(): ?string
    {
        return $this->courseOfferingSourcedId;
    }

    public function getCourseSectionSourcedId(): ?string
    {
        return $this->courseSectionSourcedId;
    }

    public function getOutcomeServiceUrl(): ?string
    {
        return $this->outcomeServiceUrl;
    }

    public function getPersonSourcedId(): ?string
    {
        return $this->personSourcedId;
    }

    public function getResultSourcedId(): ?string
    {
        return $this->resultSourcedId;
    }

    public function normalize(): array
    {
        return array_filter([
            'course_offering_sourcedid' => $this->courseOfferingSourcedId,
            'course_section_sourcedid' => $this->courseSectionSourcedId,
            'outcome_service_url' => $this->outcomeServiceUrl,
            'person_sourcedid' => $this->personSourcedId,
            'result_sourcedid' => $this->resultSourcedId,
        ]);
    }

    public static function denormalize(array $claimData): LisClaim
    {
        return new self(
            $claimData['course_offering_sourcedid'] ?? null,
            $claimData['course_section_sourcedid'] ?? null,
            $claimData['outcome_service_url'] ?? null,
            $claimData['person_sourcedid'] ?? null,
            $claimData['result_sourcedid'] ?? null
        );
    }
}
