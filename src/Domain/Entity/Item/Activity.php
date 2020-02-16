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

namespace Friendica\Domain\Entity\Item;

use Friendica\BaseEntity;
use Friendica\Domain\Entity\Item;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table item-activity
 *
 * Activities for items
 */
class Activity extends BaseEntity
{
	/** @var int */
	private $id;

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * @var int
	 * Id of the item-uri table entry that contains the item uri
	 */
	private $uriId;

	/**
	 * @var string
	 * RIPEMD-128 hash from uri
	 */
	private $uriHash = '';

	/**
	 * @var string
	 */
	private $activity = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uri' => $this->uri,
			'uri-id' => $this->uriId,
			'uri-hash' => $this->uriHash,
			'activity' => $this->activity,
		];
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get Thread
	 *
	 * @return Thread
	 */
	public function getThread()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for iid is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * @param string $uri
	 * Set
	 */
	public function setUri(string $uri)
	{
		$this->uri = $uri;
	}

	/**
	 * @return int
	 * Get Id of the item-uri table entry that contains the item uri
	 */
	public function getUriId()
	{
		return $this->uriId;
	}

	/**
	 * @param int $uriId
	 * Set Id of the item-uri table entry that contains the item uri
	 */
	public function setUriId(int $uriId)
	{
		$this->uriId = $uriId;
	}

	/**
	 * Get \ItemUri
	 *
	 * @return \ItemUri
	 */
	public function getItemUri()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get RIPEMD-128 hash from uri
	 */
	public function getUriHash()
	{
		return $this->uriHash;
	}

	/**
	 * @param string $uriHash
	 * Set RIPEMD-128 hash from uri
	 */
	public function setUriHash(string $uriHash)
	{
		$this->uriHash = $uriHash;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getActivity()
	{
		return $this->activity;
	}

	/**
	 * @param string $activity
	 * Set
	 */
	public function setActivity(string $activity)
	{
		$this->activity = $activity;
	}
}
