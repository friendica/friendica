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
 * Class Activity
 *
 * @see https://docs.joinmastodon.org/entities/activity
 */
class Activity extends BaseDataTransferObject
{
	/** @var string (UNIX Timestamp) */
	protected $week;
	/** @var string */
	protected $statuses;
	/** @var string */
	protected $logins;
	/** @var string */
	protected $registrations;

	/**
	 * Creates an activity
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $week, int $statuses, int $logins, int $registrations)
	{
		$this->week = (string)$week;
		$this->statuses = (string)$statuses;
		$this->logins = (string)$logins;
		$this->registrations = (string)$registrations;
	}
}
