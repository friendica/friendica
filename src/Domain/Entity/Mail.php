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
 * Entity class for table mail
 *
 * private messages
 */
class Mail extends BaseEntity
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
	 * @var string
	 * A unique identifier for this private message
	 */
	private $guid = '';

	/**
	 * @var string
	 * name of the sender
	 */
	private $fromName = '';

	/**
	 * @var string
	 * contact photo link of the sender
	 */
	private $fromPhoto = '';

	/**
	 * @var string
	 * profile linke of the sender
	 */
	private $fromUrl = '';

	/**
	 * @var string
	 * contact.id
	 */
	private $contactId = '';

	/**
	 * @var int
	 * conv.id
	 */
	private $convid = '0';

	/**
	 * @var string
	 */
	private $title = '';

	/**
	 * @var string
	 */
	private $body;

	/**
	 * @var bool
	 * if message visited it is 1
	 */
	private $seen = '0';

	/**
	 * @var bool
	 */
	private $reply = '0';

	/**
	 * @var bool
	 */
	private $replied = '0';

	/**
	 * @var bool
	 * if sender not in the contact table this is 1
	 */
	private $unknown = '0';

	/**
	 * @var string
	 */
	private $uri = '';

	/**
	 * @var string
	 */
	private $parentUri = '';

	/**
	 * @var string
	 * creation time of the private message
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
			'guid' => $this->guid,
			'from-name' => $this->fromName,
			'from-photo' => $this->fromPhoto,
			'from-url' => $this->fromUrl,
			'contact-id' => $this->contactId,
			'convid' => $this->convid,
			'title' => $this->title,
			'body' => $this->body,
			'seen' => $this->seen,
			'reply' => $this->reply,
			'replied' => $this->replied,
			'unknown' => $this->unknown,
			'uri' => $this->uri,
			'parent-uri' => $this->parentUri,
			'created' => $this->created,
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
	 * @return string
	 * Get A unique identifier for this private message
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set A unique identifier for this private message
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
	}

	/**
	 * @return string
	 * Get name of the sender
	 */
	public function getFromName()
	{
		return $this->fromName;
	}

	/**
	 * @param string $fromName
	 * Set name of the sender
	 */
	public function setFromName(string $fromName)
	{
		$this->fromName = $fromName;
	}

	/**
	 * @return string
	 * Get contact photo link of the sender
	 */
	public function getFromPhoto()
	{
		return $this->fromPhoto;
	}

	/**
	 * @param string $fromPhoto
	 * Set contact photo link of the sender
	 */
	public function setFromPhoto(string $fromPhoto)
	{
		$this->fromPhoto = $fromPhoto;
	}

	/**
	 * @return string
	 * Get profile linke of the sender
	 */
	public function getFromUrl()
	{
		return $this->fromUrl;
	}

	/**
	 * @param string $fromUrl
	 * Set profile linke of the sender
	 */
	public function setFromUrl(string $fromUrl)
	{
		$this->fromUrl = $fromUrl;
	}

	/**
	 * @return string
	 * Get contact.id
	 */
	public function getContactId()
	{
		return $this->contactId;
	}

	/**
	 * @param string $contactId
	 * Set contact.id
	 */
	public function setContactId(string $contactId)
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
	 * @return int
	 * Get conv.id
	 */
	public function getConvid()
	{
		return $this->convid;
	}

	/**
	 * @param int $convid
	 * Set conv.id
	 */
	public function setConvid(int $convid)
	{
		$this->convid = $convid;
	}

	/**
	 * Get Conv
	 *
	 * @return Conv
	 */
	public function getConv()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
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
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $body
	 * Set
	 */
	public function setBody(string $body)
	{
		$this->body = $body;
	}

	/**
	 * @return bool
	 * Get if message visited it is 1
	 */
	public function getSeen()
	{
		return $this->seen;
	}

	/**
	 * @param bool $seen
	 * Set if message visited it is 1
	 */
	public function setSeen(bool $seen)
	{
		$this->seen = $seen;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getReply()
	{
		return $this->reply;
	}

	/**
	 * @param bool $reply
	 * Set
	 */
	public function setReply(bool $reply)
	{
		$this->reply = $reply;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getReplied()
	{
		return $this->replied;
	}

	/**
	 * @param bool $replied
	 * Set
	 */
	public function setReplied(bool $replied)
	{
		$this->replied = $replied;
	}

	/**
	 * @return bool
	 * Get if sender not in the contact table this is 1
	 */
	public function getUnknown()
	{
		return $this->unknown;
	}

	/**
	 * @param bool $unknown
	 * Set if sender not in the contact table this is 1
	 */
	public function setUnknown(bool $unknown)
	{
		$this->unknown = $unknown;
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
	 * Get
	 */
	public function getParentUri()
	{
		return $this->parentUri;
	}

	/**
	 * @param string $parentUri
	 * Set
	 */
	public function setParentUri(string $parentUri)
	{
		$this->parentUri = $parentUri;
	}

	/**
	 * @return string
	 * Get creation time of the private message
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set creation time of the private message
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}
}
