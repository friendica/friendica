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

declare(strict_types=1);

namespace Friendica\Database;

use Exception;
use Throwable;

/**
 * A database fatal exception, which shouldn't occur
 */
class DatabaseException extends Exception
{
	protected $query;

	/**
	 * Construct the exception. Note: The message is NOT binary safe.
	 *
	 * @link https://php.net/manual/en/exception.construct.php
	 *
	 * @param string    $message  The Database error message.
	 * @param int       $code     The Database error code.
	 * @param string    $query    The Database error query.
	 * @param Throwable $previous [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct(string $message, int $code, string $query, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __toString()
	{
		return sprintf('Database error %d "%s" at "%s"', $this->message, $this->code, $this->query);
	}
}
