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

namespace OAT\Library\Lti1p3Core\Security\Jwt\Decoder;

use JsonException;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Parsing\Decoder;

/**
 * @codeCoverageIgnore
 */
class AssociativeDecoder extends Decoder
{
    public function jsonDecode($json)
    {
        if (PHP_VERSION_ID < 70300) {
            $data = json_decode($json, true);

            if (json_last_error() != JSON_ERROR_NONE) {
                throw CannotDecodeContent::jsonIssues(new JsonException(json_last_error_msg()));
            }

            return $data;
        }

        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw CannotDecodeContent::jsonIssues($exception);
        }
    }

}
