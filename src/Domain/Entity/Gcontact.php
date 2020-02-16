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
 * Entity class for table gcontact
 *
 * global contacts
 */
class Gcontact extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 * Name that this contact is known by
	 */
	private $name = '';

	/**
	 * @var string
	 * Nick- and user name of the contact
	 */
	private $nick = '';

	/**
	 * @var string
	 * Link to the contacts profile page
	 */
	private $url = '';

	/**
	 * @var string
	 */
	private $nurl = '';

	/**
	 * @var string
	 * Link to the profile photo
	 */
	private $photo = '';

	/**
	 * @var string
	 */
	private $connect = '';

	/**
	 * @var string
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $updated = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $lastContact = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $lastFailure = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $archiveDate = '0001-01-01 00:00:00';

	/**
	 * @var bool
	 */
	private $archived = '0';

	/**
	 * @var string
	 */
	private $location = '';

	/**
	 * @var string
	 */
	private $about;

	/**
	 * @var string
	 * puplic keywords (interests)
	 */
	private $keywords;

	/**
	 * @var string
	 */
	private $gender = '';

	/**
	 * @var string
	 */
	private $birthday = '0001-01-01';

	/**
	 * @var bool
	 * 1 if contact is forum account
	 */
	private $community = '0';

	/**
	 * @var string
	 */
	private $contactType = '-1';

	/**
	 * @var bool
	 * 1 = should be hidden from search
	 */
	private $hide = '0';

	/**
	 * @var bool
	 * 1 = contact posts nsfw content
	 */
	private $nsfw = '0';

	/**
	 * @var string
	 * social network protocol
	 */
	private $network = '';

	/**
	 * @var string
	 */
	private $addr = '';

	/**
	 * @var string
	 */
	private $notify;

	/**
	 * @var string
	 */
	private $alias = '';

	/**
	 * @var string
	 */
	private $generation = '0';

	/**
	 * @var string
	 * baseurl of the contacts server
	 */
	private $serverUrl = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'nick' => $this->nick,
			'url' => $this->url,
			'nurl' => $this->nurl,
			'photo' => $this->photo,
			'connect' => $this->connect,
			'created' => $this->created,
			'updated' => $this->updated,
			'last_contact' => $this->lastContact,
			'last_failure' => $this->lastFailure,
			'archive_date' => $this->archiveDate,
			'archived' => $this->archived,
			'location' => $this->location,
			'about' => $this->about,
			'keywords' => $this->keywords,
			'gender' => $this->gender,
			'birthday' => $this->birthday,
			'community' => $this->community,
			'contact-type' => $this->contactType,
			'hide' => $this->hide,
			'nsfw' => $this->nsfw,
			'network' => $this->network,
			'addr' => $this->addr,
			'notify' => $this->notify,
			'alias' => $this->alias,
			'generation' => $this->generation,
			'server_url' => $this->serverUrl,
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
	 * Get Name that this contact is known by
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * Set Name that this contact is known by
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 * Get Nick- and user name of the contact
	 */
	public function getNick()
	{
		return $this->nick;
	}

	/**
	 * @param string $nick
	 * Set Nick- and user name of the contact
	 */
	public function setNick(string $nick)
	{
		$this->nick = $nick;
	}

	/**
	 * @return string
	 * Get Link to the contacts profile page
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 * Set Link to the contacts profile page
	 */
	public function setUrl(string $url)
	{
		$this->url = $url;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNurl()
	{
		return $this->nurl;
	}

	/**
	 * @param string $nurl
	 * Set
	 */
	public function setNurl(string $nurl)
	{
		$this->nurl = $nurl;
	}

	/**
	 * @return string
	 * Get Link to the profile photo
	 */
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @param string $photo
	 * Set Link to the profile photo
	 */
	public function setPhoto(string $photo)
	{
		$this->photo = $photo;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getConnect()
	{
		return $this->connect;
	}

	/**
	 * @param string $connect
	 * Set
	 */
	public function setConnect(string $connect)
	{
		$this->connect = $connect;
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

	/**
	 * @return string
	 * Get
	 */
	public function getLastContact()
	{
		return $this->lastContact;
	}

	/**
	 * @param string $lastContact
	 * Set
	 */
	public function setLastContact(string $lastContact)
	{
		$this->lastContact = $lastContact;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getLastFailure()
	{
		return $this->lastFailure;
	}

	/**
	 * @param string $lastFailure
	 * Set
	 */
	public function setLastFailure(string $lastFailure)
	{
		$this->lastFailure = $lastFailure;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getArchiveDate()
	{
		return $this->archiveDate;
	}

	/**
	 * @param string $archiveDate
	 * Set
	 */
	public function setArchiveDate(string $archiveDate)
	{
		$this->archiveDate = $archiveDate;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getArchived()
	{
		return $this->archived;
	}

	/**
	 * @param bool $archived
	 * Set
	 */
	public function setArchived(bool $archived)
	{
		$this->archived = $archived;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @param string $location
	 * Set
	 */
	public function setLocation(string $location)
	{
		$this->location = $location;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAbout()
	{
		return $this->about;
	}

	/**
	 * @param string $about
	 * Set
	 */
	public function setAbout(string $about)
	{
		$this->about = $about;
	}

	/**
	 * @return string
	 * Get puplic keywords (interests)
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	/**
	 * @param string $keywords
	 * Set puplic keywords (interests)
	 */
	public function setKeywords(string $keywords)
	{
		$this->keywords = $keywords;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getGender()
	{
		return $this->gender;
	}

	/**
	 * @param string $gender
	 * Set
	 */
	public function setGender(string $gender)
	{
		$this->gender = $gender;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getBirthday()
	{
		return $this->birthday;
	}

	/**
	 * @param string $birthday
	 * Set
	 */
	public function setBirthday(string $birthday)
	{
		$this->birthday = $birthday;
	}

	/**
	 * @return bool
	 * Get 1 if contact is forum account
	 */
	public function getCommunity()
	{
		return $this->community;
	}

	/**
	 * @param bool $community
	 * Set 1 if contact is forum account
	 */
	public function setCommunity(bool $community)
	{
		$this->community = $community;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getContactType()
	{
		return $this->contactType;
	}

	/**
	 * @param string $contactType
	 * Set
	 */
	public function setContactType(string $contactType)
	{
		$this->contactType = $contactType;
	}

	/**
	 * @return bool
	 * Get 1 = should be hidden from search
	 */
	public function getHide()
	{
		return $this->hide;
	}

	/**
	 * @param bool $hide
	 * Set 1 = should be hidden from search
	 */
	public function setHide(bool $hide)
	{
		$this->hide = $hide;
	}

	/**
	 * @return bool
	 * Get 1 = contact posts nsfw content
	 */
	public function getNsfw()
	{
		return $this->nsfw;
	}

	/**
	 * @param bool $nsfw
	 * Set 1 = contact posts nsfw content
	 */
	public function setNsfw(bool $nsfw)
	{
		$this->nsfw = $nsfw;
	}

	/**
	 * @return string
	 * Get social network protocol
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $network
	 * Set social network protocol
	 */
	public function setNetwork(string $network)
	{
		$this->network = $network;
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
	public function getGeneration()
	{
		return $this->generation;
	}

	/**
	 * @param string $generation
	 * Set
	 */
	public function setGeneration(string $generation)
	{
		$this->generation = $generation;
	}

	/**
	 * @return string
	 * Get baseurl of the contacts server
	 */
	public function getServerUrl()
	{
		return $this->serverUrl;
	}

	/**
	 * @param string $serverUrl
	 * Set baseurl of the contacts server
	 */
	public function setServerUrl(string $serverUrl)
	{
		$this->serverUrl = $serverUrl;
	}
}
