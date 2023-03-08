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

namespace Friendica\Contact\Introduction\Entity;

use Friendica\BaseEntity;

/**
 * @property-read int $uid
 * @property-read int $cid Either a public contact id (DFRN suggestion) or user-specific id (Contact::addRelationship)
 * @property-read int|null $sid
 * @property-read bool $knowyou
 * @property-read string $note
 * @property-read string $hash
 * @property-read \DateTime $datetime
 * @property-read bool $ignore
 * @property-read int|null $id
 */
class Introduction extends BaseEntity
{
	/** @var int */
	protected $uid;
	/** @var int */
	protected $cid;
	/** @var int|null */
	protected $sid;
	/** @var bool */
	protected $knowyou;
	/** @var string */
	protected $note;
	/** @var string */
	protected $hash;
	/** @var \DateTime */
	protected $datetime;
	/** @var bool */
	protected $ignore;
	/** @var int|null */
	protected $id;

	/**
	 * @param int       $uid
	 * @param int       $cid
	 * @param int|null  $sid
	 * @param bool      $knowyou
	 * @param string    $note
	 * @param string    $hash
	 * @param \DateTime $datetime
	 * @param bool      $ignore
	 * @param int|null  $id
	 */
	public function __construct(int $uid, int $cid, ?int $sid, bool $knowyou, string $note, string $hash, \DateTime $datetime, bool $ignore, ?int $id)
	{
		$this->uid      = $uid;
		$this->cid      = $cid;
		$this->sid      = $sid;
		$this->knowyou  = $knowyou;
		$this->note     = $note;
		$this->hash     = $hash;
		$this->datetime = $datetime;
		$this->ignore   = $ignore;
		$this->id       = $id;
	}

	/**
	 * Ignore the current Introduction
	 */
	public function ignore()
	{
		$this->ignore = true;
	}
}
