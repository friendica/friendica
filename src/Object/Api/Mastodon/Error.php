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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Error
 *
 * @see https://docs.joinmastodon.org/entities/error
 */
class Error extends BaseDataTransferObject
{
	/** @var string */
	protected $error;
	/** @var string */
	protected $error_description;

	/**
	 * Creates an error record
	 *
	 * @param string $error
	 * @param string error_description
	 */
	public function __construct(string $error, string $error_description)
	{
		$this->error             = $error;
		$this->error_description = $error_description;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$error = parent::toArray();

		if (empty($error['error_description'])) {
			unset($error['error_description']);
		}

		return $error;
	}
}
