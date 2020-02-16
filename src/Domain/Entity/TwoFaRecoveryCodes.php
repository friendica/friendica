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
 * Entity class for table 2fa_recovery_codes
 *
 * Two-factor authentication recovery codes
 */
class TwoFaRecoveryCodes extends BaseEntity
{
	/**
	 * @var int
	 * User ID
	 */
	private $uid;

	/**
	 * @var string
	 * Recovery code string
	 */
	private $code;

	/**
	 * @var string
	 * Datetime the code was generated
	 */
	private $generated;

	/**
	 * @var string
	 * Datetime the code was used
	 */
	private $used;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'uid' => $this->uid,
			'code' => $this->code,
			'generated' => $this->generated,
			'used' => $this->used,
		];
	}

	/**
	 * @return int
	 * Get User ID
	 */
	public function getUid()
	{
		return $this->uid;
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
	 * Get Recovery code string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @return string
	 * Get Datetime the code was generated
	 */
	public function getGenerated()
	{
		return $this->generated;
	}

	/**
	 * @param string $generated
	 * Set Datetime the code was generated
	 */
	public function setGenerated(string $generated)
	{
		$this->generated = $generated;
	}

	/**
	 * @return string
	 * Get Datetime the code was used
	 */
	public function getUsed()
	{
		return $this->used;
	}

	/**
	 * @param string $used
	 * Set Datetime the code was used
	 */
	public function setUsed(string $used)
	{
		$this->used = $used;
	}
}
