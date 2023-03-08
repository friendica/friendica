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

namespace Friendica\Network;

use Exception;

/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has been extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */
abstract class HTTPException extends Exception
{
	protected $httpdesc    = '';
	protected $explanation = '';

	public function __construct(string $message = '', Exception $previous = null)
	{
		parent::__construct($message, $this->code, $previous);
	}

	public function getDescription()
	{
		return $this->httpdesc;
	}

	public function getExplanation()
	{
		return $this->explanation;
	}
}
