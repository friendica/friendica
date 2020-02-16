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
 * Entity class for table pconfig
 *
 * personal (per user) configuration storage
 */
class Pconfig extends BaseEntity
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 */
	private $cat = '';

	/**
	 * @var string
	 */
	private $k = '';

	/**
	 * @var string
	 */
	private $v;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'cat' => $this->cat,
			'k' => $this->k,
			'v' => $this->v,
		];
	}

	/**
	 * @return int
	 * Get
	 */
	public function getId()
	{
		return $this->id;
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

	/**
	 * @return string
	 * Get
	 */
	public function getCat()
	{
		return $this->cat;
	}

	/**
	 * @param string $cat
	 * Set
	 */
	public function setCat(string $cat)
	{
		$this->cat = $cat;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getK()
	{
		return $this->k;
	}

	/**
	 * @param string $k
	 * Set
	 */
	public function setK(string $k)
	{
		$this->k = $k;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getV()
	{
		return $this->v;
	}

	/**
	 * @param string $v
	 * Set
	 */
	public function setV(string $v)
	{
		$this->v = $v;
	}
}
