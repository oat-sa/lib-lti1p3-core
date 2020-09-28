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
use OAT\Library\Lti1p3Core\Resource\ResourceCollectionInterface;

/**
 * @see https://www.imsglobal.org/spec/lti-dl/v2p0#content-items
 */
class DeepLinkingContentItemsClaim implements MessagePayloadClaimInterface
{
    /** @var array */
    private $contentItems;

    public function __construct(array $contentItems = [])
    {
        $this->contentItems = $contentItems;
    }

    public static function fromResourceCollection(ResourceCollectionInterface $collection): DeepLinkingContentItemsClaim
    {
        return new self($collection->normalize());
    }

    public function getContentItems(): array
    {
        return $this->contentItems;
    }

    public static function getClaimName(): string
    {
        return LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_CONTENT_ITEMS;
    }

    public function normalize(): array
    {
        return $this->contentItems;
    }

    public static function denormalize(array $claimData): DeepLinkingContentItemsClaim
    {
        return new self($claimData);
    }
}
