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
 * Entity class for table auth_codes
 *
 * OAuth usage
 */
class AuthCodes extends BaseEntity
{
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $clientId = '';

	/**
	 * @var string
	 */
	private $redirectUri = '';

	/**
	 * @var int
	 */
	private $expires = '0';

	/**
	 * @var string
	 */
	private $scope = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'client_id' => $this->clientId,
			'redirect_uri' => $this->redirectUri,
			'expires' => $this->expires,
			'scope' => $this->scope,
		];
	}

	/**
	 * @return string
	 * Get
	 */
	public function getId()
	{
		return $this->id;
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
	 * @param string $clientId
	 * Set
	 */
	public function setClientId(string $clientId)
	{
		$this->clientId = $clientId;
	}

	/**
	 * Get Clients
	 *
	 * @return Clients
	 */
	public function getClients()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for clientId is not implemented yet');
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
	 * @return int
	 * Get
	 */
	public function getExpires()
	{
		return $this->expires;
	}

	/**
	 * @param int $expires
	 * Set
	 */
	public function setExpires(int $expires)
	{
		$this->expires = $expires;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getScope()
	{
		return $this->scope;
	}

	/**
	 * @param string $scope
	 * Set
	 */
	public function setScope(string $scope)
	{
		$this->scope = $scope;
	}
}
