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

namespace Friendica\Contact\FriendSuggest\Entity;

use Friendica\BaseEntity;

/**
 * Model for interacting with a friend suggestion
 *
 * @property-read int $uid
 * @property-read int $cid
 * @property-read string $name
 * @property-read string $url
 * @property-read string $request
 * @property-read string $photo
 * @property-read string $note
 * @property-read \DateTime created
 * @property-read int|null $id
 */
class FriendSuggest extends BaseEntity
{
	/** @var int */
	protected $uid;
	/** @var int */
	protected $cid;
	/** @var string */
	protected $name;
	/** @var string */
	protected $url;
	/** @var string */
	protected $request;
	/** @var string */
	protected $photo;
	/** @var string */
	protected $note;
	/** @var \DateTime */
	protected $created;
	/** @var int|null */
	protected $id;

	/**
	 * @param int       $uid
	 * @param int       $cid
	 * @param string    $name
	 * @param string    $url
	 * @param string    $request
	 * @param string    $photo
	 * @param string    $note
	 * @param \DateTime $created
	 * @param int|null  $id
	 */
	public function __construct(int $uid, int $cid, string $name, string $url, string $request, string $photo, string $note, \DateTime $created, ?int $id = null)
	{
		$this->uid     = $uid;
		$this->cid     = $cid;
		$this->name    = $name;
		$this->url     = $url;
		$this->request = $request;
		$this->photo   = $photo;
		$this->note    = $note;
		$this->created = $created;
		$this->id      = $id;
	}
}
