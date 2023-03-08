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

namespace Friendica\Network\HTTPClient\Capability;

use Psr\Http\Message\MessageInterface;

/**
 * Temporary class to map Friendica used variables based on PSR-7 HTTPResponse
 */
interface ICanHandleHttpResponses
{
	/**
	 * Gets the Return Code
	 *
	 * @return string The Return Code
	 */
	public function getReturnCode(): string;

	/**
	 * Returns the Content Type
	 *
	 * @return string the Content Type
	 */
	public function getContentType(): string;

	/**
	 * Returns the headers
	 *
	 * @param string $header optional header field. Return all fields if empty
	 *
	 * @return string[] the headers or the specified content of the header variable
	 *@see MessageInterface::getHeader()
	 *
	 */
	public function getHeader(string $header);

	/**
	 * Returns all headers
	 *
	 * @see MessageInterface::getHeaders()
	 * @return string[][]
	 */
	public function getHeaders();

	/**
	 * Check if a specified header exists
	 *
	 * @see MessageInterface::hasHeader()
	 * @param string $field header field
	 * @return boolean "true" if header exists
	 */
	public function inHeader(string $field): bool;

	/**
	 * Returns the headers as an associated array
	 * @see MessageInterface::getHeaders()
	 * @deprecated
	 *
	 * @return string[][] associated header array
	 */
	public function getHeaderArray();

	/**
	 * @return bool
	 */
	public function isSuccess(): bool;

	/**
	 * @return string
	 */
	public function getUrl(): string;

	/**
	 * @return string
	 */
	public function getRedirectUrl(): string;

	/**
	 * Getter for body
	 *
	 * @see MessageInterface::getBody()
	 * @return string
	 */
	public function getBody();

	/**
	 * @return boolean
	 */
	public function isRedirectUrl(): bool;

	/**
	 * @return integer
	 */
	public function getErrorNumber(): int;

	/**
	 * @return string
	 */
	public function getError(): string;

	/**
	 * @return boolean
	 */
	public function isTimeout(): bool;
}
