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
 * Entity class for table poll
 *
 * Currently unused table for storing poll results
 */
class Poll extends BaseEntity
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
	private $qzero;

	/**
	 * @var string
	 */
	private $qone;

	/**
	 * @var string
	 */
	private $qtwo;

	/**
	 * @var string
	 */
	private $qthree;

	/**
	 * @var string
	 */
	private $qfour;

	/**
	 * @var string
	 */
	private $qfive;

	/**
	 * @var string
	 */
	private $qsix;

	/**
	 * @var string
	 */
	private $qseven;

	/**
	 * @var string
	 */
	private $qeight;

	/**
	 * @var string
	 */
	private $qnine;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'q0' => $this->qzero,
			'q1' => $this->qone,
			'q2' => $this->qtwo,
			'q3' => $this->qthree,
			'q4' => $this->qfour,
			'q5' => $this->qfive,
			'q6' => $this->qsix,
			'q7' => $this->qseven,
			'q8' => $this->qeight,
			'q9' => $this->qnine,
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
	public function getQzero()
	{
		return $this->qzero;
	}

	/**
	 * @param string $qzero
	 * Set
	 */
	public function setQzero(string $qzero)
	{
		$this->qzero = $qzero;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQone()
	{
		return $this->qone;
	}

	/**
	 * @param string $qone
	 * Set
	 */
	public function setQone(string $qone)
	{
		$this->qone = $qone;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQtwo()
	{
		return $this->qtwo;
	}

	/**
	 * @param string $qtwo
	 * Set
	 */
	public function setQtwo(string $qtwo)
	{
		$this->qtwo = $qtwo;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQthree()
	{
		return $this->qthree;
	}

	/**
	 * @param string $qthree
	 * Set
	 */
	public function setQthree(string $qthree)
	{
		$this->qthree = $qthree;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQfour()
	{
		return $this->qfour;
	}

	/**
	 * @param string $qfour
	 * Set
	 */
	public function setQfour(string $qfour)
	{
		$this->qfour = $qfour;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQfive()
	{
		return $this->qfive;
	}

	/**
	 * @param string $qfive
	 * Set
	 */
	public function setQfive(string $qfive)
	{
		$this->qfive = $qfive;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQsix()
	{
		return $this->qsix;
	}

	/**
	 * @param string $qsix
	 * Set
	 */
	public function setQsix(string $qsix)
	{
		$this->qsix = $qsix;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQseven()
	{
		return $this->qseven;
	}

	/**
	 * @param string $qseven
	 * Set
	 */
	public function setQseven(string $qseven)
	{
		$this->qseven = $qseven;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQeight()
	{
		return $this->qeight;
	}

	/**
	 * @param string $qeight
	 * Set
	 */
	public function setQeight(string $qeight)
	{
		$this->qeight = $qeight;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getQnine()
	{
		return $this->qnine;
	}

	/**
	 * @param string $qnine
	 * Set
	 */
	public function setQnine(string $qnine)
	{
		$this->qnine = $qnine;
	}
}
