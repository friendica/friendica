<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Http\Message\ResponseInterface;

class Response implements ICanCreateResponses
{
	/**
	 * @var string[]
	 */
	protected $headers = [];
	/**
	 * @var string
	 */
	protected $content = '';
	/**
	 * @var string
	 */
	protected $type = self::TYPE_HTML;

	protected $status = 200;

	protected $reason = null;

	/**
	 * {@inheritDoc}
	 */
	public function setHeader(?string $header = null, ?string $key = null): void
	{
		if (!isset($header) && !empty($key)) {
			unset($this->headers[$key]);
		}

		if (isset($header)) {
			if (empty($key)) {
				$this->headers[] = $header;
			} else {
				$this->headers[$key] = $header;
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function addContent($content): void
	{
		$this->content .= $content;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setType(string $type, ?string $content_type = null): void
	{
		if (!in_array($type, static::ALLOWED_TYPES)) {
			throw new InternalServerErrorException('wrong type');
		}

		switch ($type) {
			case static::TYPE_HTML:
				$content_type = $content_type ?? 'text/html; charset=utf-8';
				break;
			case static::TYPE_JSON:
				$content_type = $content_type ?? 'application/json';
				break;
			case static::TYPE_XML:
				$content_type = $content_type ?? 'text/xml';
				break;
			case static::TYPE_RSS:
				$content_type = $content_type ?? 'application/rss+xml';
				break;
			case static::TYPE_ATOM:
				$content_type = $content_type ?? 'application/atom+xml';
				break;
		}

		$this->setHeader($content_type, 'Content-type');

		$this->type = $type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setStatus(int $status = 200, ?string $reason = null): void
	{
		$this->status = $status;
		$this->reason = $reason;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function generate(): ResponseInterface
	{
		// Setting the response type as an X-header for direct usage
		$this->headers[static::X_HEADER] = $this->type;

		return new \GuzzleHttp\Psr7\Response($this->status, $this->headers, $this->content, '1.1', $this->reason);
	}
}
