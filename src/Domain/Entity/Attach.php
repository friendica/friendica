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
 * Entity class for table attach
 *
 * file attachments
 */
class Attach extends BaseEntity
{
	/**
	 * @var int
	 * generated index
	 */
	private $id;

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 * hash
	 */
	private $hash = '';

	/**
	 * @var string
	 * filename of original
	 */
	private $filename = '';

	/**
	 * @var string
	 * mimetype
	 */
	private $filetype = '';

	/**
	 * @var int
	 * size in bytes
	 */
	private $filesize = '0';

	/**
	 * @var int
	 * file data
	 */
	private $data;

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
	 * Access Control - list of allowed contact.id '<19><78>
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
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'hash' => $this->hash,
			'filename' => $this->filename,
			'filetype' => $this->filetype,
			'filesize' => $this->filesize,
			'data' => $this->data,
			'created' => $this->created,
			'edited' => $this->edited,
			'allow_cid' => $this->allowCid,
			'allow_gid' => $this->allowGid,
			'deny_cid' => $this->denyCid,
			'deny_gid' => $this->denyGid,
			'backend-class' => $this->backendClass,
			'backend-ref' => $this->backendRef,
		];
	}

	/**
	 * @return int
	 * Get generated index
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
	 * @return string
	 * Get hash
	 */
	public function getHash()
	{
		return $this->hash;
	}

	/**
	 * @param string $hash
	 * Set hash
	 */
	public function setHash(string $hash)
	{
		$this->hash = $hash;
	}

	/**
	 * @return string
	 * Get filename of original
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @param string $filename
	 * Set filename of original
	 */
	public function setFilename(string $filename)
	{
		$this->filename = $filename;
	}

	/**
	 * @return string
	 * Get mimetype
	 */
	public function getFiletype()
	{
		return $this->filetype;
	}

	/**
	 * @param string $filetype
	 * Set mimetype
	 */
	public function setFiletype(string $filetype)
	{
		$this->filetype = $filetype;
	}

	/**
	 * @return int
	 * Get size in bytes
	 */
	public function getFilesize()
	{
		return $this->filesize;
	}

	/**
	 * @param int $filesize
	 * Set size in bytes
	 */
	public function setFilesize(int $filesize)
	{
		$this->filesize = $filesize;
	}

	/**
	 * @return int
	 * Get file data
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param int $data
	 * Set file data
	 */
	public function setData(int $data)
	{
		$this->data = $data;
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
	 * Get Access Control - list of allowed contact.id '<19><78>
	 */
	public function getAllowCid()
	{
		return $this->allowCid;
	}

	/**
	 * @param string $allowCid
	 * Set Access Control - list of allowed contact.id '<19><78>
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
}
