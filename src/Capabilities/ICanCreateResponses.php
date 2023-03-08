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

namespace Friendica\Capabilities;

use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Http\Message\ResponseInterface;

interface ICanCreateResponses
{
	/**
	 * This constant helps to find the specific return type of responses inside the headers array
	 */
	const X_HEADER = 'X-RESPONSE-TYPE';

	const TYPE_HTML  = 'html';
	const TYPE_XML   = 'xml';
	const TYPE_JSON  = 'json';
	const TYPE_ATOM  = 'atom';
	const TYPE_RSS   = 'rss';
	const TYPE_BLANK = 'blank';

	const ALLOWED_TYPES = [
		self::TYPE_HTML,
		self::TYPE_XML,
		self::TYPE_JSON,
		self::TYPE_ATOM,
		self::TYPE_RSS,
		self::TYPE_BLANK,
	];

	/**
	 * Adds a header entry to the module response
	 *
	 * @param string $header
	 * @param string|null $key
	 */
	public function setHeader(string $header, ?string $key = null): void;

	/**
	 * Adds output content to the module response
	 *
	 * @param mixed $content
	 */
	public function addContent($content): void;

	/**
	 * Sets the response type of the current request
	 *
	 * @param string $type
	 * @param string|null $content_type (optional) overrides the direct content_type, otherwise set the default one
	 *
	 * @throws InternalServerErrorException
	 */
	public function setType(string $type, ?string $content_type = null): void;

	/**
	 * Sets the status and the reason for the response
	 *
	 * @param int $status The HTTP status code
	 * @param null|string $reason Reason phrase (when empty a default will be used based on the status code)
	 *
	 * @return void
	 */
	public function setStatus(int $status = 200, ?string $reason = null): void;

	/**
	 * Creates a PSR-7 compliant interface
	 * @see https://www.php-fig.org/psr/psr-7/
	 *
	 * @return ResponseInterface
	 */
	public function generate(): ResponseInterface;
}
