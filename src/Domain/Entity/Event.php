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
 * Entity class for table event
 *
 * Events
 */
class Event extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 */
	private $guid = '';

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var int
	 * contact_id (ID of the contact in contact table)
	 */
	private $cid = '0';

	/**
	 * @var string
	 */
	private $uri = '';

	/**
	 * @var string
	 * creation time
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * last edit time
	 */
	private $edited = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * event start time
	 */
	private $start = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * event end time
	 */
	private $finish = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * short description or title of the event
	 */
	private $summary;

	/**
	 * @var string
	 * event description
	 */
	private $desc;

	/**
	 * @var string
	 * event location
	 */
	private $location;

	/**
	 * @var string
	 * event or birthday
	 */
	private $type = '';

	/**
	 * @var bool
	 * if event does have no end this is 1
	 */
	private $nofinish = '0';

	/**
	 * @var bool
	 * adjust to timezone of the recipient (0 or 1)
	 */
	private $adjust = '1';

	/**
	 * @var bool
	 * 0 or 1
	 */
	private $ignore = '0';

	/**
	 * @var string
	 * Access Control - list of allowed contact.id '<19><78>'
	 */
	private $allowCid;

	/**
	 * @var string
	 * Access Control - list of allowed groups
	 */
	private $allowGid;

	/**
	 * @var string
	 * Access Control - list of denied contact.id
	 */
	private $denyCid;

	/**
	 * @var string
	 * Access Control - list of denied groups
	 */
	private $denyGid;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'guid' => $this->guid,
			'uid' => $this->uid,
			'cid' => $this->cid,
			'uri' => $this->uri,
			'created' => $this->created,
			'edited' => $this->edited,
			'start' => $this->start,
			'finish' => $this->finish,
			'summary' => $this->summary,
			'desc' => $this->desc,
			'location' => $this->location,
			'type' => $this->type,
			'nofinish' => $this->nofinish,
			'adjust' => $this->adjust,
			'ignore' => $this->ignore,
			'allow_cid' => $this->allowCid,
			'allow_gid' => $this->allowGid,
			'deny_cid' => $this->denyCid,
			'deny_gid' => $this->denyGid,
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
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
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
	 * @return int
	 * Get contact_id (ID of the contact in contact table)
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @param int $cid
	 * Set contact_id (ID of the contact in contact table)
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
	 * @return string
	 * Get creation time
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set creation time
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get last edit time
	 */
	public function getEdited()
	{
		return $this->edited;
	}

	/**
	 * @param string $edited
	 * Set last edit time
	 */
	public function setEdited(string $edited)
	{
		$this->edited = $edited;
	}

	/**
	 * @return string
	 * Get event start time
	 */
	public function getStart()
	{
		return $this->start;
	}

	/**
	 * @param string $start
	 * Set event start time
	 */
	public function setStart(string $start)
	{
		$this->start = $start;
	}

	/**
	 * @return string
	 * Get event end time
	 */
	public function getFinish()
	{
		return $this->finish;
	}

	/**
	 * @param string $finish
	 * Set event end time
	 */
	public function setFinish(string $finish)
	{
		$this->finish = $finish;
	}

	/**
	 * @return string
	 * Get short description or title of the event
	 */
	public function getSummary()
	{
		return $this->summary;
	}

	/**
	 * @param string $summary
	 * Set short description or title of the event
	 */
	public function setSummary(string $summary)
	{
		$this->summary = $summary;
	}

	/**
	 * @return string
	 * Get event description
	 */
	public function getDesc()
	{
		return $this->desc;
	}

	/**
	 * @param string $desc
	 * Set event description
	 */
	public function setDesc(string $desc)
	{
		$this->desc = $desc;
	}

	/**
	 * @return string
	 * Get event location
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @param string $location
	 * Set event location
	 */
	public function setLocation(string $location)
	{
		$this->location = $location;
	}

	/**
	 * @return string
	 * Get event or birthday
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * Set event or birthday
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return bool
	 * Get if event does have no end this is 1
	 */
	public function getNofinish()
	{
		return $this->nofinish;
	}

	/**
	 * @param bool $nofinish
	 * Set if event does have no end this is 1
	 */
	public function setNofinish(bool $nofinish)
	{
		$this->nofinish = $nofinish;
	}

	/**
	 * @return bool
	 * Get adjust to timezone of the recipient (0 or 1)
	 */
	public function getAdjust()
	{
		return $this->adjust;
	}

	/**
	 * @param bool $adjust
	 * Set adjust to timezone of the recipient (0 or 1)
	 */
	public function setAdjust(bool $adjust)
	{
		$this->adjust = $adjust;
	}

	/**
	 * @return bool
	 * Get 0 or 1
	 */
	public function getIgnore()
	{
		return $this->ignore;
	}

	/**
	 * @param bool $ignore
	 * Set 0 or 1
	 */
	public function setIgnore(bool $ignore)
	{
		$this->ignore = $ignore;
	}

	/**
	 * @return string
	 * Get Access Control - list of allowed contact.id '<19><78>'
	 */
	public function getAllowCid()
	{
		return $this->allowCid;
	}

	/**
	 * @param string $allowCid
	 * Set Access Control - list of allowed contact.id '<19><78>'
	 */
	public function setAllowCid(string $allowCid)
	{
		$this->allowCid = $allowCid;
	}

	/**
	 * @return string
	 * Get Access Control - list of allowed groups
	 */
	public function getAllowGid()
	{
		return $this->allowGid;
	}

	/**
	 * @param string $allowGid
	 * Set Access Control - list of allowed groups
	 */
	public function setAllowGid(string $allowGid)
	{
		$this->allowGid = $allowGid;
	}

	/**
	 * @return string
	 * Get Access Control - list of denied contact.id
	 */
	public function getDenyCid()
	{
		return $this->denyCid;
	}

	/**
	 * @param string $denyCid
	 * Set Access Control - list of denied contact.id
	 */
	public function setDenyCid(string $denyCid)
	{
		$this->denyCid = $denyCid;
	}

	/**
	 * @return string
	 * Get Access Control - list of denied groups
	 */
	public function getDenyGid()
	{
		return $this->denyGid;
	}

	/**
	 * @param string $denyGid
	 * Set Access Control - list of denied groups
	 */
	public function setDenyGid(string $denyGid)
	{
		$this->denyGid = $denyGid;
	}
}
