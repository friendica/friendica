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

/**
 * Entity class for table fcontact
 *
 * Diaspora compatible contacts - used in the Diaspora implementation
 */
class Fcontact extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 * unique id
	 */
	private $guid = '';

	/**
	 * @var string
	 */
	private $url = '';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var string
	 */
	private $photo = '';

	/**
	 * @var string
	 */
	private $request = '';

	/**
	 * @var string
	 */
	private $nick = '';

	/**
	 * @var string
	 */
	private $addr = '';

	/**
	 * @var string
	 */
	private $batch = '';

	/**
	 * @var string
	 */
	private $notify = '';

	/**
	 * @var string
	 */
	private $poll = '';

	/**
	 * @var string
	 */
	private $confirm = '';

	/**
	 * @var string
	 */
	private $priority = '0';

	/**
	 * @var string
	 */
	private $network = '';

	/**
	 * @var string
	 */
	private $alias = '';

	/**
	 * @var string
	 */
	private $pubkey;

	/**
	 * @var string
	 */
	private $updated = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'guid' => $this->guid,
			'url' => $this->url,
			'name' => $this->name,
			'photo' => $this->photo,
			'request' => $this->request,
			'nick' => $this->nick,
			'addr' => $this->addr,
			'batch' => $this->batch,
			'notify' => $this->notify,
			'poll' => $this->poll,
			'confirm' => $this->confirm,
			'priority' => $this->priority,
			'network' => $this->network,
			'alias' => $this->alias,
			'pubkey' => $this->pubkey,
			'updated' => $this->updated,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 * Get unique id
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set unique id
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
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
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @param string $photo
	 * Set
	 */
	public function setPhoto(string $photo)
	{
		$this->photo = $photo;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param string $request
	 * Set
	 */
	public function setRequest(string $request)
	{
		$this->request = $request;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNick()
	{
		return $this->nick;
	}

	/**
	 * @param string $nick
	 * Set
	 */
	public function setNick(string $nick)
	{
		$this->nick = $nick;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAddr()
	{
		return $this->addr;
	}

	/**
	 * @param string $addr
	 * Set
	 */
	public function setAddr(string $addr)
	{
		$this->addr = $addr;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getBatch()
	{
		return $this->batch;
	}

	/**
	 * @param string $batch
	 * Set
	 */
	public function setBatch(string $batch)
	{
		$this->batch = $batch;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNotify()
	{
		return $this->notify;
	}

	/**
	 * @param string $notify
	 * Set
	 */
	public function setNotify(string $notify)
	{
		$this->notify = $notify;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPoll()
	{
		return $this->poll;
	}

	/**
	 * @param string $poll
	 * Set
	 */
	public function setPoll(string $poll)
	{
		$this->poll = $poll;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getConfirm()
	{
		return $this->confirm;
	}

	/**
	 * @param string $confirm
	 * Set
	 */
	public function setConfirm(string $confirm)
	{
		$this->confirm = $confirm;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param string $priority
	 * Set
	 */
	public function setPriority(string $priority)
	{
		$this->priority = $priority;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $network
	 * Set
	 */
	public function setNetwork(string $network)
	{
		$this->network = $network;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * @param string $alias
	 * Set
	 */
	public function setAlias(string $alias)
	{
		$this->alias = $alias;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPubkey()
	{
		return $this->pubkey;
	}

	/**
	 * @param string $pubkey
	 * Set
	 */
	public function setPubkey(string $pubkey)
	{
		$this->pubkey = $pubkey;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getUpdated()
	{
		return $this->updated;
	}

	/**
	 * @param string $updated
	 * Set
	 */
	public function setUpdated(string $updated)
	{
		$this->updated = $updated;
	}
}
