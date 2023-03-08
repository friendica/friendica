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

namespace Friendica\Object\Api\Mastodon\Status;

/**
 * Class Counts
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class Counts
{
	/** @var int */
	protected $replies;
	/** @var int */
	protected $reblogs;
	/** @var int */
	protected $favourites;

	/** @var int */
	protected $dislikes;

	/**
	 * Creates a status count object
	 *
	 * @param int $replies
	 * @param int $reblogs
	 * @param int $favourites
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $replies, int $reblogs, int $favourites, int $dislikes)
	{
		$this->replies    = $replies;
		$this->reblogs    = $reblogs;
		$this->favourites = $favourites;
		$this->dislikes   = $dislikes;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
