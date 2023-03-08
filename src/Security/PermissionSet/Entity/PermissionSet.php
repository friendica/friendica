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

namespace Friendica\Security\PermissionSet\Entity;

use Friendica\BaseEntity;
use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;

/**
 * @property-read int|null $id
 * @property-read int      $uid
 * @property-read string[] $allow_cid
 * @property-read string[] $allow_gid
 * @property-read string[] $deny_cid
 * @property-read string[] $deny_gid
 */
class PermissionSet extends BaseEntity
{
	/** @var int|null */
	protected $id;
	/** @var int */
	protected $uid;
	/** @var string[] */
	protected $allow_cid;
	/** @var string[] */
	protected $allow_gid;
	/** @var string[] */
	protected $deny_cid;
	/** @var string[] */
	protected $deny_gid;

	/**
	 * @param int|null $id
	 * @param int      $uid
	 * @param string[] $allow_cid
	 * @param string[] $allow_gid
	 * @param string[] $deny_cid
	 * @param string[] $deny_gid
	 *
	 * @see \Friendica\Security\PermissionSet\Factory\PermissionSet
	 */
	public function __construct(int $uid, array $allow_cid = [], array $allow_gid = [], array $deny_cid = [], array $deny_gid = [], int $id = null)
	{
		$this->id        = $id;
		$this->uid       = $uid;
		$this->allow_cid = $allow_cid;
		$this->allow_gid = $allow_gid;
		$this->deny_cid  = $deny_cid;
		$this->deny_gid  = $deny_gid;
	}

	/**
	 * Checks, if the current PermissionSet is a/the public PermissionSet
	 *
	 * @return bool
	 */
	public function isPublic(): bool
	{
		return (($this->id === PermissionSetRepository::PUBLIC) ||
				(is_null($this->id) &&
				 empty($this->allow_cid) &&
				 empty($this->allow_gid) &&
				 empty($this->deny_cid) &&
				 empty($this->deny_gid)));
	}

	/**
	 * Creates a new Entity with a new allowed_cid list (wipes the id because it isn't the same entity anymore)
	 *
	 * @param array $allow_cid
	 *
	 * @return $this
	 */
	public function withAllowedContacts(array $allow_cid): PermissionSet
	{
		$clone = clone $this;

		$clone->allow_cid = $allow_cid;
		$clone->id        = null;

		return $clone;
	}
}
