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
 * Class UserAttributes
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class UserAttributes
{
	/** @var bool */
	protected $favourited;
	/** @var bool */
	protected $reblogged;
	/** @var bool */
	protected $muted;
	/** @var bool */
	protected $bookmarked;
	/** @var bool */
	protected $pinned;

	/**
	 * Creates a authorized user attributes object
	 *
	 * @param bool $favourited
	 * @param bool $reblogged
	 * @param bool $muted
	 * @param bool $bookmarked
	 * @param bool $pinned
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(bool $favourited, bool $reblogged, bool $muted, bool $bookmarked, bool $pinned)
	{
		$this->favourited = $favourited;
		$this->reblogged = $reblogged;
		$this->muted = $muted;
		$this->bookmarked = $bookmarked;
		$this->pinned = $pinned;
	}

	public function __get($name) {
		return $this->$name;
	}
}
