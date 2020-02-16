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
 * Entity class for table participation
 *
 * Storage for participation messages from Diaspora
 */
class Participation extends BaseEntity
{
	/**
	 * @var int
	 */
	private $iid;

	/**
	 * @var string
	 */
	private $server;

	/**
	 * @var int
	 */
	private $cid;

	/**
	 * @var int
	 */
	private $fid;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'iid' => $this->iid,
			'server' => $this->server,
			'cid' => $this->cid,
			'fid' => $this->fid,
		];
	}

	/**
	 * @return int
	 * Get
	 */
	public function getIid()
	{
		return $this->iid;
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
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @param int $cid
	 * Set
	 */
	public function setCid(int $cid)
	{
		$this->cid = $cid;
	}

	/**
	 * Get Contact
	 *
	 * @return Contact
	 */
	public function getContact()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return int
	 * Get
	 */
	public function getFid()
	{
		return $this->fid;
	}

	/**
	 * @param int $fid
	 * Set
	 */
	public function setFid(int $fid)
	{
		$this->fid = $fid;
	}

	/**
	 * Get Fcontact
	 *
	 * @return Fcontact
	 */
	public function getFcontact()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}
}
