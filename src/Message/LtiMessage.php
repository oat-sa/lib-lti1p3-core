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

namespace OAT\Library\Lti1p3Core\Message;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @see http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details
 */
class LtiMessage implements LtiMessageInterface
{
    /** @var string */
    private $url;

    /** @var CollectionInterface */
    private $parameters;

    public function __construct(string $url, array $parameters = [])
    {
        $this->url = $url;
        $this->parameters = (new Collection())->add($parameters);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getParameters(): CollectionInterface
    {
        return $this->parameters;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public static function fromServerRequest(ServerRequestInterface $request): LtiMessageInterface
    {
        $method = strtoupper($request->getMethod());

        if ($method === 'GET') {
            parse_str($request->getUri()->getQuery(), $parameters);

            return new static($request->getUri()->__toString(), $parameters);
        }

        if ($method === 'POST') {
            return new static($request->getUri()->__toString(), $request->getParsedBody());
        }

        throw new LtiException(sprintf('Unsupported request method %s', $method));
    }

    public function toUrl(): string
    {
        $separator = false !== strpos($this->url, '?')
            ? '&'
            : '?';

        return sprintf('%s%s%s', $this->url, $separator, http_build_query(array_filter($this->getParameters()->all())));
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
        $parameters = array_filter($this->getParameters()->all());
        $formId = sprintf('launch_%s', md5($this->url . implode('-', $parameters)));

        foreach ($parameters as $name => $value) {
            $formInputs[] = sprintf('<input type="hidden" name="%s" value="%s"/>', $name, $value);
        }

        $autoSubmitScript = sprintf(
            '<script>window.onload=function(){document.getElementById("%s").submit()}</script>',
            $formId
        );

        return sprintf(
            '<form id="%s" action="%s" method="POST">%s</form>%s',
            $formId,
            $this->url,
            implode('', $formInputs),
            $autoSubmit ? $autoSubmitScript : ''
        );
    }
}
