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

namespace Friendica\Object\Search;

use Friendica\Model\Search;

/**
 * A search result for contact searching
 *
 * @see Search for details
 */
class ContactResult implements IResult
{
	/**
	 * @var int
	 */
	private $cid;
	/**
	 * @var int
	 */
	private $pCid;
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $addr;
	/**
	 * @var string
	 */
	private $item;
	/**
	 * @var string
	 */
	private $url;
	/**
	 * @var string
	 */
	private $photo;
	/**
	 * @var string
	 */
	private $tags;
	/**
	 * @var string
	 */
	private $network;

	/**
	 * @return int
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @return int
	 */
	public function getPCid()
	{
		return $this->pCid;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getAddr()
	{
		return $this->addr;
	}

	/**
	 * @return string
	 */
	public function getItem()
	{
		return $this->item;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @return string
	 */
	public function getTags()
	{
		return $this->tags;
	}

	/**
	 * @return string
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $name
	 * @param string $addr
	 * @param string $item
	 * @param string $url
	 * @param string $photo
	 * @param string $network
	 * @param int    $cid
	 * @param int    $pCid
	 * @param string $tags
	 */
	public function __construct($name, $addr, $item, $url, $photo, $network, $cid = 0, $pCid = 0, $tags = '')
	{
		$this->name    = $name;
		$this->addr    = $addr;
		$this->item    = $item;
		$this->url     = $url;
		$this->photo   = $photo;
		$this->network = $network;

		$this->cid  = $cid;
		$this->pCid = $pCid;
		$this->tags = $tags;
	}
}
