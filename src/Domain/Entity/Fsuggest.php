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
 * Entity class for table fsuggest
 *
 * friend suggestion stuff
 */
class Fsuggest extends BaseEntity
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
	 * @var int
	 */
	private $cid = '0';

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
	private $request = '';

	/**
	 * @var string
	 */
	private $photo = '';

	/**
	 * @var string
	 */
	private $note;

	/**
	 * @var string
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'cid' => $this->cid,
			'name' => $this->name,
			'url' => $this->url,
			'request' => $this->request,
			'photo' => $this->photo,
			'note' => $this->note,
			'created' => $this->created,
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
	 * @return int
	 * Get
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @param int $cid
	 * Set
	 */
	public function setCid(int $cid)
	{
		$this->cid = $cid;
	}

	/**
	 * Get Contact
	 *
	 * @return Contact
	 */
	public function getContact()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
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
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * @param string $note
	 * Set
	 */
	public function setNote(string $note)
	{
		$this->note = $note;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}
}
