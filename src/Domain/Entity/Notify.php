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
 * Entity class for table notify
 *
 * notifications
 */
class Notify extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 */
	private $type = '0';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var string
	 */
	private $url = '';

	/**
	 * @var string
	 */
	private $photo = '';

	/**
	 * @var string
	 */
	private $date = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $msg;

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 */
	private $link = '';

	/**
	 * @var int
	 * item.id
	 */
	private $iid = '0';

	/**
	 * @var int
	 */
	private $parent = '0';

	/**
	 * @var bool
	 */
	private $seen = '0';

	/**
	 * @var string
	 */
	private $verb = '';

	/**
	 * @var string
	 */
	private $otype = '';

	/**
	 * @var string
	 * Cached bbcode parsing of name
	 */
	private $nameCache;

	/**
	 * @var string
	 * Cached bbcode parsing of msg
	 */
	private $msgCache;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'type' => $this->type,
			'name' => $this->name,
			'url' => $this->url,
			'photo' => $this->photo,
			'date' => $this->date,
			'msg' => $this->msg,
			'uid' => $this->uid,
			'link' => $this->link,
			'iid' => $this->iid,
			'parent' => $this->parent,
			'seen' => $this->seen,
			'verb' => $this->verb,
			'otype' => $this->otype,
			'name_cache' => $this->nameCache,
			'msg_cache' => $this->msgCache,
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
	 * Get
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * Set
	 */
	public function setType(string $type)
	{
		$this->type = $type;
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
	public function getDate()
	{
		return $this->date;
	}

	/**
	 * @param string $date
	 * Set
	 */
	public function setDate(string $date)
	{
		$this->date = $date;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getMsg()
	{
		return $this->msg;
	}

	/**
	 * @param string $msg
	 * Set
	 */
	public function setMsg(string $msg)
	{
		$this->msg = $msg;
	}

	/**
	 * @return int
	 * Get Owner User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set Owner User id
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
	public function getLink()
	{
		return $this->link;
	}

	/**
	 * @param string $link
	 * Set
	 */
	public function setLink(string $link)
	{
		$this->link = $link;
	}

	/**
	 * @return int
	 * Get item.id
	 */
	public function getIid()
	{
		return $this->iid;
	}

	/**
	 * @param int $iid
	 * Set item.id
	 */
	public function setIid(int $iid)
	{
		$this->iid = $iid;
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
	 * @return int
	 * Get
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param int $parent
	 * Set
	 */
	public function setParent(int $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getSeen()
	{
		return $this->seen;
	}

	/**
	 * @param bool $seen
	 * Set
	 */
	public function setSeen(bool $seen)
	{
		$this->seen = $seen;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getVerb()
	{
		return $this->verb;
	}

	/**
	 * @param string $verb
	 * Set
	 */
	public function setVerb(string $verb)
	{
		$this->verb = $verb;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getOtype()
	{
		return $this->otype;
	}

	/**
	 * @param string $otype
	 * Set
	 */
	public function setOtype(string $otype)
	{
		$this->otype = $otype;
	}

	/**
	 * @return string
	 * Get Cached bbcode parsing of name
	 */
	public function getNameCache()
	{
		return $this->nameCache;
	}

	/**
	 * @param string $nameCache
	 * Set Cached bbcode parsing of name
	 */
	public function setNameCache(string $nameCache)
	{
		$this->nameCache = $nameCache;
	}

	/**
	 * @return string
	 * Get Cached bbcode parsing of msg
	 */
	public function getMsgCache()
	{
		return $this->msgCache;
	}

	/**
	 * @param string $msgCache
	 * Set Cached bbcode parsing of msg
	 */
	public function setMsgCache(string $msgCache)
	{
		$this->msgCache = $msgCache;
	}
}
