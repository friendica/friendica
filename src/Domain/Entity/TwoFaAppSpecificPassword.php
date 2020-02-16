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
 * Entity class for table 2fa_app_specific_password
 *
 * Two-factor app-specific _password
 */
class TwoFaAppSpecificPassword extends BaseEntity
{
	/**
	 * @var int
	 * Password ID for revocation
	 */
	private $id;

	/**
	 * @var int
	 * User ID
	 */
	private $uid;

	/**
	 * @var string
	 * Description of the usage of the password
	 */
	private $description;

	/**
	 * @var string
	 * Hashed password
	 */
	private $hashedPassword;

	/**
	 * @var string
	 * Datetime the password was generated
	 */
	private $generated;

	/**
	 * @var string
	 * Datetime the password was last used
	 */
	private $lastUsed;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'description' => $this->description,
			'hashed_password' => $this->hashedPassword,
			'generated' => $this->generated,
			'last_used' => $this->lastUsed,
		];
	}

	/**
	 * @return int
	 * Get Password ID for revocation
	 */
	public function getId()
	{
		return $this->id;
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
	 * @param int $uid
	 * Set User ID
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
	 * Get Description of the usage of the password
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 * Set Description of the usage of the password
	 */
	public function setDescription(string $description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 * Get Hashed password
	 */
	public function getHashedPassword()
	{
		return $this->hashedPassword;
	}

	/**
	 * @param string $hashedPassword
	 * Set Hashed password
	 */
	public function setHashedPassword(string $hashedPassword)
	{
		$this->hashedPassword = $hashedPassword;
	}

	/**
	 * @return string
	 * Get Datetime the password was generated
	 */
	public function getGenerated()
	{
		return $this->generated;
	}

	/**
	 * @param string $generated
	 * Set Datetime the password was generated
	 */
	public function setGenerated(string $generated)
	{
		$this->generated = $generated;
	}

	/**
	 * @return string
	 * Get Datetime the password was last used
	 */
	public function getLastUsed()
	{
		return $this->lastUsed;
	}

	/**
	 * @param string $lastUsed
	 * Set Datetime the password was last used
	 */
	public function setLastUsed(string $lastUsed)
	{
		$this->lastUsed = $lastUsed;
	}
}
