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
abstract class AbstractLaunchRequest implements LaunchRequestInterface
{
    /** @var string */
    private $url;

    /** @var array */
    private $parameters;

    public function __construct(string $url, array $parameters = [])
    {
        $this->url = $url;
        $this->parameters = $parameters;
    }

    public static function fromServerRequest(ServerRequestInterface $request): LaunchRequestInterface
    {
        return new static($request->getUri()->__toString(), $request->getParsedBody());
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $parameterName, string $default = null): ?string
    {
        return $this->parameters[$parameterName] ?? $default;
    }

    public function toUrl(): string
    {
        return sprintf('%s?%s', $this->url, http_build_query(array_filter($this->parameters)));
    }

    public function toHtmlLink(string $title, array $attributes = []): string
    {
        $htmlAttributes = [];

        foreach ($attributes as $name => $value) {
            $htmlAttributes[] = sprintf('%s="%s"', $name, $value);
        }

        return sprintf('<a href="%s" %s>%s</a>', $this->toUrl(), implode(' ', $htmlAttributes), $title);
    }

    public function toHtmlRedirectForm(bool $autoSubmit = true): string
    {
        $formInputs = [];
        $formId = sprintf('launch_%s', md5($this->url . implode('-', $this->parameters)));

        foreach (array_filter($this->parameters) as $name => $value) {
            $formInputs[] = sprintf('<input type="hidden" name="%s" value="%s"/>', $name, $value);
        }

        $autoSubmitScript = sprintf('<script>document.getElementById("%s").submit();</script>', $formId);

        return sprintf(
            '<form id="%s" action="%s" method="POST">%s</form>%s',
            $formId,
            $this->url,
            implode('', $formInputs),
            $autoSubmit ? $autoSubmitScript : ''
        );
    }
}
