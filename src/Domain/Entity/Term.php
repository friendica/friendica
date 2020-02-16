<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table term
 *
 * item taxonomy (categories, tags, etc.) table
 */
class Term extends BaseEntity
{
	/**
	 * @var int
	 */
	private $tid;

	/**
	 * @var int
	 */
	private $oid = '0';

	/**
	 * @var string
	 */
	private $otype = '0';

	/**
	 * @var string
	 */
	private $type = '0';

	/**
	 * @var string
	 */
	private $term = '';

	/**
	 * @var string
	 */
	private $url = '';

	/**
	 * @var string
	 */
	private $guid = '';

	/**
	 * @var string
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $received = '0001-01-01 00:00:00';

	/**
	 * @var bool
	 */
	private $global = '0';

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'tid' => $this->tid,
			'oid' => $this->oid,
			'otype' => $this->otype,
			'type' => $this->type,
			'term' => $this->term,
			'url' => $this->url,
			'guid' => $this->guid,
			'created' => $this->created,
			'received' => $this->received,
			'global' => $this->global,
			'uid' => $this->uid,
		];
	}

	/**
	 * @return int
	 * Get
	 */
	public function getTid()
	{
		return $this->tid;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getOid()
	{
		return $this->oid;
	}

	/**
	 * @param int $oid
	 * Set
	 */
	public function setOid(int $oid)
	{
		$this->oid = $oid;
	}

	/**
	 * Get Item
	 *
	 * @return Item
	 */
	public function getItem()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getOtype()
	{
		return $this->otype;
	}

	/**
	 * @param string $otype
	 * Set
	 */
	public function setOtype(string $otype)
	{
		$this->otype = $otype;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * Set
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getTerm()
	{
		return $this->term;
	}

	/**
	 * @param string $term
	 * Set
	 */
	public function setTerm(string $term)
	{
		$this->term = $term;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 * Set
	 */
	public function setUrl(string $url)
	{
		$this->url = $url;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getReceived()
	{
		return $this->received;
	}

	/**
	 * @param string $received
	 * Set
	 */
	public function setReceived(string $received)
	{
		$this->received = $received;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getGlobal()
	{
		return $this->global;
	}

	/**
	 * @param bool $global
	 * Set
	 */
	public function setGlobal(bool $global)
	{
		$this->global = $global;
	}

	/**
	 * @return int
	 * Get User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set User id
	 */
	public function setUid(int $uid)
	{
		$this->uid = $uid;
	}

	/**
	 * Get User
	 *
	 * @return User
	 */
	public function getUser()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for uid is not implemented yet');
	}
}
