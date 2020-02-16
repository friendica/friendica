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
 * Entity class for table clients
 *
 * OAuth usage
 */
class Clients extends BaseEntity
{
	/**
	 * @var string
	 */
	private $clientId;

	/**
	 * @var string
	 */
	private $pw = '';

	/**
	 * @var string
	 */
	private $redirectUri = '';

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $icon;

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
			'client_id' => $this->clientId,
			'pw' => $this->pw,
			'redirect_uri' => $this->redirectUri,
			'name' => $this->name,
			'icon' => $this->icon,
			'uid' => $this->uid,
		];
	}

	/**
	 * @return string
	 * Get
	 */
	public function getClientId()
	{
		return $this->clientId;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPw()
	{
		return $this->pw;
	}

	/**
	 * @param string $pw
	 * Set
	 */
	public function setPw(string $pw)
	{
		$this->pw = $pw;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getRedirectUri()
	{
		return $this->redirectUri;
	}

	/**
	 * @param string $redirectUri
	 * Set
	 */
	public function setRedirectUri(string $redirectUri)
	{
		$this->redirectUri = $redirectUri;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * Set
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $icon
	 * Set
	 */
	public function setIcon(string $icon)
	{
		$this->icon = $icon;
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
