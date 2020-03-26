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

namespace OAT\Library\Lti1p3Core\Launch;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-launch-0
 */
interface LaunchRequestInterface
{
    public function getUrl(): string;

    public function getParameters(): array;

    public function getMandatoryParameter(string $parameterName): string;

    public function getParameter(string $parameterName, string $default = null): ?string;

    public static function fromServerRequest(ServerRequestInterface $request): LaunchRequestInterface;

    public function toUrl(): string;

    public function toHtmlLink(string $title, array $attributes = []): string;

    public function toHtmlRedirectForm(bool $autoSubmit = true): string;
}
