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
 * Entity class for table apcontact
 *
 * ActivityPub compatible contacts - used in the ActivityPub implementation
 */
class Apcontact extends BaseEntity
{
	/**
	 * @var string
	 * URL of the contact
	 */
	private $url;

	/**
	 * @var string
	 */
	private $uuid;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $following;

	/**
	 * @var string
	 */
	private $followers;

	/**
	 * @var string
	 */
	private $inbox;

	/**
	 * @var string
	 */
	private $outbox;

	/**
	 * @var string
	 */
	private $sharedinbox;

	/**
	 * @var bool
	 */
	private $manuallyApprove;

	/**
	 * @var string
	 */
	private $nick = '';

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $about;

	/**
	 * @var string
	 */
	private $photo;

	/**
	 * @var string
	 */
	private $addr;

	/**
	 * @var string
	 */
	private $alias;

	/**
	 * @var string
	 */
	private $pubkey;

	/**
	 * @var string
	 * baseurl of the ap contact
	 */
	private $baseurl;

	/**
	 * @var string
	 * Name of the contact's system
	 */
	private $generator;

	/**
	 * @var int
	 * Number of following contacts
	 */
	private $followingCount = 0;

	/**
	 * @var int
	 * Number of followers
	 */
	private $followersCount = 0;

	/**
	 * @var int
	 * Number of posts
	 */
	private $statusesCount = 0;

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
			'url' => $this->url,
			'uuid' => $this->uuid,
			'type' => $this->type,
			'following' => $this->following,
			'followers' => $this->followers,
			'inbox' => $this->inbox,
			'outbox' => $this->outbox,
			'sharedinbox' => $this->sharedinbox,
			'manually-approve' => $this->manuallyApprove,
			'nick' => $this->nick,
			'name' => $this->name,
			'about' => $this->about,
			'photo' => $this->photo,
			'addr' => $this->addr,
			'alias' => $this->alias,
			'pubkey' => $this->pubkey,
			'baseurl' => $this->baseurl,
			'generator' => $this->generator,
			'following_count' => $this->followingCount,
			'followers_count' => $this->followersCount,
			'statuses_count' => $this->statusesCount,
			'updated' => $this->updated,
		];
	}

	/**
	 * @return string
	 * Get URL of the contact
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getUuid()
	{
		return $this->uuid;
	}

	/**
	 * @param string $uuid
	 * Set
	 */
	public function setUuid(string $uuid)
	{
		$this->uuid = $uuid;
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
	public function getFollowing()
	{
		return $this->following;
	}

	/**
	 * @param string $following
	 * Set
	 */
	public function setFollowing(string $following)
	{
		$this->following = $following;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getFollowers()
	{
		return $this->followers;
	}

	/**
	 * @param string $followers
	 * Set
	 */
	public function setFollowers(string $followers)
	{
		$this->followers = $followers;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getInbox()
	{
		return $this->inbox;
	}

	/**
	 * @param string $inbox
	 * Set
	 */
	public function setInbox(string $inbox)
	{
		$this->inbox = $inbox;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getOutbox()
	{
		return $this->outbox;
	}

	/**
	 * @param string $outbox
	 * Set
	 */
	public function setOutbox(string $outbox)
	{
		$this->outbox = $outbox;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSharedinbox()
	{
		return $this->sharedinbox;
	}

	/**
	 * @param string $sharedinbox
	 * Set
	 */
	public function setSharedinbox(string $sharedinbox)
	{
		$this->sharedinbox = $sharedinbox;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getManuallyApprove()
	{
		return $this->manuallyApprove;
	}

	/**
	 * @param bool $manuallyApprove
	 * Set
	 */
	public function setManuallyApprove(bool $manuallyApprove)
	{
		$this->manuallyApprove = $manuallyApprove;
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
	 * Get baseurl of the ap contact
	 */
	public function getBaseurl()
	{
		return $this->baseurl;
	}

	/**
	 * @param string $baseurl
	 * Set baseurl of the ap contact
	 */
	public function setBaseurl(string $baseurl)
	{
		$this->baseurl = $baseurl;
	}

	/**
	 * @return string
	 * Get Name of the contact's system
	 */
	public function getGenerator()
	{
		return $this->generator;
	}

	/**
	 * @param string $generator
	 * Set Name of the contact's system
	 */
	public function setGenerator(string $generator)
	{
		$this->generator = $generator;
	}

	/**
	 * @return int
	 * Get Number of following contacts
	 */
	public function getFollowingCount()
	{
		return $this->followingCount;
	}

	/**
	 * @param int $followingCount
	 * Set Number of following contacts
	 */
	public function setFollowingCount(int $followingCount)
	{
		$this->followingCount = $followingCount;
	}

	/**
	 * @return int
	 * Get Number of followers
	 */
	public function getFollowersCount()
	{
		return $this->followersCount;
	}

	/**
	 * @param int $followersCount
	 * Set Number of followers
	 */
	public function setFollowersCount(int $followersCount)
	{
		$this->followersCount = $followersCount;
	}

	/**
	 * @return int
	 * Get Number of posts
	 */
	public function getStatusesCount()
	{
		return $this->statusesCount;
	}

	/**
	 * @param int $statusesCount
	 * Set Number of posts
	 */
	public function setStatusesCount(int $statusesCount)
	{
		$this->statusesCount = $statusesCount;
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
