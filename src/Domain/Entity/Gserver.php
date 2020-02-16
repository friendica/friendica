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
 * Entity class for table gserver
 *
 * Global servers
 */
class Gserver extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 */
	private $url = '';

	/**
	 * @var string
	 */
	private $nurl = '';

	/**
	 * @var string
	 */
	private $version = '';

	/**
	 * @var string
	 */
	private $siteName = '';

	/**
	 * @var string
	 */
	private $info;

	/**
	 * @var string
	 */
	private $registerPolicy = '0';

	/**
	 * @var int
	 * Number of registered users
	 */
	private $registeredUsers = '0';

	/**
	 * @var string
	 * Type of directory service (Poco, Mastodon)
	 */
	private $directoryType = '0';

	/**
	 * @var string
	 */
	private $poco = '';

	/**
	 * @var string
	 */
	private $noscrape = '';

	/**
	 * @var string
	 */
	private $network = '';

	/**
	 * @var string
	 */
	private $platform = '';

	/**
	 * @var bool
	 * Has the server subscribed to the relay system
	 */
	private $relaySubscribe = '0';

	/**
	 * @var string
	 * The scope of messages that the server wants to get
	 */
	private $relayScope = '';

	/**
	 * @var string
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $lastPocoQuery = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $lastContact = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $lastFailure = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'url' => $this->url,
			'nurl' => $this->nurl,
			'version' => $this->version,
			'site_name' => $this->siteName,
			'info' => $this->info,
			'register_policy' => $this->registerPolicy,
			'registered-users' => $this->registeredUsers,
			'directory-type' => $this->directoryType,
			'poco' => $this->poco,
			'noscrape' => $this->noscrape,
			'network' => $this->network,
			'platform' => $this->platform,
			'relay-subscribe' => $this->relaySubscribe,
			'relay-scope' => $this->relayScope,
			'created' => $this->created,
			'last_poco_query' => $this->lastPocoQuery,
			'last_contact' => $this->lastContact,
			'last_failure' => $this->lastFailure,
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
	 * Get
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 * Set
	 */
	public function setVersion(string $version)
	{
		$this->version = $version;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSiteName()
	{
		return $this->siteName;
	}

	/**
	 * @param string $siteName
	 * Set
	 */
	public function setSiteName(string $siteName)
	{
		$this->siteName = $siteName;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getInfo()
	{
		return $this->info;
	}

	/**
	 * @param string $info
	 * Set
	 */
	public function setInfo(string $info)
	{
		$this->info = $info;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getRegisterPolicy()
	{
		return $this->registerPolicy;
	}

	/**
	 * @param string $registerPolicy
	 * Set
	 */
	public function setRegisterPolicy(string $registerPolicy)
	{
		$this->registerPolicy = $registerPolicy;
	}

	/**
	 * @return int
	 * Get Number of registered users
	 */
	public function getRegisteredUsers()
	{
		return $this->registeredUsers;
	}

	/**
	 * @param int $registeredUsers
	 * Set Number of registered users
	 */
	public function setRegisteredUsers(int $registeredUsers)
	{
		$this->registeredUsers = $registeredUsers;
	}

	/**
	 * @return string
	 * Get Type of directory service (Poco, Mastodon)
	 */
	public function getDirectoryType()
	{
		return $this->directoryType;
	}

	/**
	 * @param string $directoryType
	 * Set Type of directory service (Poco, Mastodon)
	 */
	public function setDirectoryType(string $directoryType)
	{
		$this->directoryType = $directoryType;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPoco()
	{
		return $this->poco;
	}

	/**
	 * @param string $poco
	 * Set
	 */
	public function setPoco(string $poco)
	{
		$this->poco = $poco;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNoscrape()
	{
		return $this->noscrape;
	}

	/**
	 * @param string $noscrape
	 * Set
	 */
	public function setNoscrape(string $noscrape)
	{
		$this->noscrape = $noscrape;
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
	public function getPlatform()
	{
		return $this->platform;
	}

	/**
	 * @param string $platform
	 * Set
	 */
	public function setPlatform(string $platform)
	{
		$this->platform = $platform;
	}

	/**
	 * @return bool
	 * Get Has the server subscribed to the relay system
	 */
	public function getRelaySubscribe()
	{
		return $this->relaySubscribe;
	}

	/**
	 * @param bool $relaySubscribe
	 * Set Has the server subscribed to the relay system
	 */
	public function setRelaySubscribe(bool $relaySubscribe)
	{
		$this->relaySubscribe = $relaySubscribe;
	}

	/**
	 * @return string
	 * Get The scope of messages that the server wants to get
	 */
	public function getRelayScope()
	{
		return $this->relayScope;
	}

	/**
	 * @param string $relayScope
	 * Set The scope of messages that the server wants to get
	 */
	public function setRelayScope(string $relayScope)
	{
		$this->relayScope = $relayScope;
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
	public function getLastPocoQuery()
	{
		return $this->lastPocoQuery;
	}

	/**
	 * @param string $lastPocoQuery
	 * Set
	 */
	public function setLastPocoQuery(string $lastPocoQuery)
	{
		$this->lastPocoQuery = $lastPocoQuery;
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
}
