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
 * @see https://www.imsglobal.org/spec/proctoring/v1p0#h.ckrfa92a27mw
 */
class AcsClaim implements MessagePayloadClaimInterface
{
    /** @var array */
    private $actions;

    /** @var string */
    private $assessmentControlUrl;

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_ACS;
    }

    public function __construct(array $actions, string $assessmentControlUrl)
    {
        $this->actions = $actions;
        $this->assessmentControlUrl = $assessmentControlUrl;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getAssessmentControlUrl(): string
    {
        return $this->assessmentControlUrl;
    }

    public function normalize(): array
    {
        return array_filter([
            'actions' => $this->actions,
            'assessment_control_url' => $this->assessmentControlUrl,
        ]);
    }

    public static function denormalize(array $claimData): AcsClaim
    {
        return new self(
            $claimData['actions'],
            $claimData['assessment_control_url']
        );
    }
}
