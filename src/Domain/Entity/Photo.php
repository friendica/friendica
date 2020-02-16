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
 * Entity class for table photo
 *
 * photo storage
 */
class Photo extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var int
	 * contact.id
	 */
	private $contactId = '0';

	/**
	 * @var string
	 * A unique identifier for this photo
	 */
	private $guid = '';

	/**
	 * @var string
	 */
	private $resourceId = '';

	/**
	 * @var string
	 * creation date
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * last edited date
	 */
	private $edited = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $title = '';

	/**
	 * @var string
	 */
	private $desc;

	/**
	 * @var string
	 * The name of the album to which the photo belongs
	 */
	private $album = '';

	/**
	 * @var string
	 */
	private $filename = '';

	/** @var string */
	private $type = 'image/jpeg';

	/**
	 * @var string
	 */
	private $height = '0';

	/**
	 * @var string
	 */
	private $width = '0';

	/**
	 * @var int
	 */
	private $datasize = '0';

	/**
	 * @var string
	 */
	private $data;

	/**
	 * @var string
	 */
	private $scale = '0';

	/**
	 * @var bool
	 */
	private $profile = '0';

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
	 * @var string
	 * Storage backend class
	 */
	private $backendClass;

	/**
	 * @var string
	 * Storage backend data reference
	 */
	private $backendRef;

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
			'uid' => $this->uid,
			'contact-id' => $this->contactId,
			'guid' => $this->guid,
			'resource-id' => $this->resourceId,
			'created' => $this->created,
			'edited' => $this->edited,
			'title' => $this->title,
			'desc' => $this->desc,
			'album' => $this->album,
			'filename' => $this->filename,
			'type' => $this->type,
			'height' => $this->height,
			'width' => $this->width,
			'datasize' => $this->datasize,
			'data' => $this->data,
			'scale' => $this->scale,
			'profile' => $this->profile,
			'allow_cid' => $this->allowCid,
			'allow_gid' => $this->allowGid,
			'deny_cid' => $this->denyCid,
			'deny_gid' => $this->denyGid,
			'backend-class' => $this->backendClass,
			'backend-ref' => $this->backendRef,
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
	 * Get contact.id
	 */
	public function getContactId()
	{
		return $this->contactId;
	}

	/**
	 * @param int $contactId
	 * Set contact.id
	 */
	public function setContactId(int $contactId)
	{
		$this->contactId = $contactId;
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
	 * Get A unique identifier for this photo
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set A unique identifier for this photo
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getResourceId()
	{
		return $this->resourceId;
	}

	/**
	 * @param string $resourceId
	 * Set
	 */
	public function setResourceId(string $resourceId)
	{
		$this->resourceId = $resourceId;
	}

	/**
	 * @return string
	 * Get creation date
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set creation date
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get last edited date
	 */
	public function getEdited()
	{
		return $this->edited;
	}

	/**
	 * @param string $edited
	 * Set last edited date
	 */
	public function setEdited(string $edited)
	{
		$this->edited = $edited;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * Set
	 */
	public function setTitle(string $title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getDesc()
	{
		return $this->desc;
	}

	/**
	 * @param string $desc
	 * Set
	 */
	public function setDesc(string $desc)
	{
		$this->desc = $desc;
	}

	/**
	 * @return string
	 * Get The name of the album to which the photo belongs
	 */
	public function getAlbum()
	{
		return $this->album;
	}

	/**
	 * @param string $album
	 * Set The name of the album to which the photo belongs
	 */
	public function setAlbum(string $album)
	{
		$this->album = $album;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @param string $filename
	 * Set
	 */
	public function setFilename(string $filename)
	{
		$this->filename = $filename;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @param string $height
	 * Set
	 */
	public function setHeight(string $height)
	{
		$this->height = $height;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param string $width
	 * Set
	 */
	public function setWidth(string $width)
	{
		$this->width = $width;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getDatasize()
	{
		return $this->datasize;
	}

	/**
	 * @param int $datasize
	 * Set
	 */
	public function setDatasize(int $datasize)
	{
		$this->datasize = $datasize;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param string $data
	 * Set
	 */
	public function setData(string $data)
	{
		$this->data = $data;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getScale()
	{
		return $this->scale;
	}

	/**
	 * @param string $scale
	 * Set
	 */
	public function setScale(string $scale)
	{
		$this->scale = $scale;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getProfile()
	{
		return $this->profile;
	}

	/**
	 * @param bool $profile
	 * Set
	 */
	public function setProfile(bool $profile)
	{
		$this->profile = $profile;
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

	/**
	 * @return string
	 * Get Storage backend class
	 */
	public function getBackendClass()
	{
		return $this->backendClass;
	}

	/**
	 * @param string $backendClass
	 * Set Storage backend class
	 */
	public function setBackendClass(string $backendClass)
	{
		$this->backendClass = $backendClass;
	}

	/**
	 * @return string
	 * Get Storage backend data reference
	 */
	public function getBackendRef()
	{
		return $this->backendRef;
	}

	/**
	 * @param string $backendRef
	 * Set Storage backend data reference
	 */
	public function setBackendRef(string $backendRef)
	{
		$this->backendRef = $backendRef;
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
