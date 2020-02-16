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
 * Entity class for table thread
 *
 * Thread related data
 */
class Thread extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $iid = '0';

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * @var int
	 */
	private $contactId = '0';

	/**
	 * @var int
	 * Item owner
	 */
	private $ownerId = '0';

	/**
	 * @var int
	 * Item author
	 */
	private $authorId = '0';

	/**
	 * @var string
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $edited = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $commented = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $received = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $changed = '0001-01-01 00:00:00';

	/**
	 * @var bool
	 */
	private $wall = '0';

	/**
	 * @var bool
	 */
	private $private = '0';

	/**
	 * @var bool
	 */
	private $pubmail = '0';

	/**
	 * @var bool
	 */
	private $moderated = '0';

	/**
	 * @var bool
	 */
	private $visible = '0';

	/**
	 * @var bool
	 */
	private $starred = '0';

	/**
	 * @var bool
	 */
	private $ignored = '0';

	/**
	 * @var string
	 * Post type (personal note, bookmark, ...)
	 */
	private $postType = '0';

	/**
	 * @var bool
	 */
	private $unseen = '1';

	/**
	 * @var bool
	 */
	private $deleted = '0';

	/**
	 * @var bool
	 */
	private $origin = '0';

	/**
	 * @var string
	 */
	private $forumMode = '0';

	/**
	 * @var bool
	 */
	private $mention = '0';

	/**
	 * @var string
	 */
	private $network = '';

	/**
	 * @var bool
	 */
	private $bookmark;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'iid' => $this->iid,
			'uid' => $this->uid,
			'contact-id' => $this->contactId,
			'owner-id' => $this->ownerId,
			'author-id' => $this->authorId,
			'created' => $this->created,
			'edited' => $this->edited,
			'commented' => $this->commented,
			'received' => $this->received,
			'changed' => $this->changed,
			'wall' => $this->wall,
			'private' => $this->private,
			'pubmail' => $this->pubmail,
			'moderated' => $this->moderated,
			'visible' => $this->visible,
			'starred' => $this->starred,
			'ignored' => $this->ignored,
			'post-type' => $this->postType,
			'unseen' => $this->unseen,
			'deleted' => $this->deleted,
			'origin' => $this->origin,
			'forum_mode' => $this->forumMode,
			'mention' => $this->mention,
			'network' => $this->network,
			'bookmark' => $this->bookmark,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getIid()
	{
		return $this->iid;
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
	public function getContactId()
	{
		return $this->contactId;
	}

	/**
	 * @param int $contactId
	 * Set
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
	 * @return int
	 * Get Item owner
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
	}

	/**
	 * @param int $ownerId
	 * Set Item owner
	 */
	public function setOwnerId(int $ownerId)
	{
		$this->ownerId = $ownerId;
	}

	/**
	 * @return int
	 * Get Item author
	 */
	public function getAuthorId()
	{
		return $this->authorId;
	}

	/**
	 * @param int $authorId
	 * Set Item author
	 */
	public function setAuthorId(int $authorId)
	{
		$this->authorId = $authorId;
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
	public function getEdited()
	{
		return $this->edited;
	}

	/**
	 * @param string $edited
	 * Set
	 */
	public function setEdited(string $edited)
	{
		$this->edited = $edited;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCommented()
	{
		return $this->commented;
	}

	/**
	 * @param string $commented
	 * Set
	 */
	public function setCommented(string $commented)
	{
		$this->commented = $commented;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getReceived()
	{
		return $this->received;
	}

	/**
	 * @param string $received
	 * Set
	 */
	public function setReceived(string $received)
	{
		$this->received = $received;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getChanged()
	{
		return $this->changed;
	}

	/**
	 * @param string $changed
	 * Set
	 */
	public function setChanged(string $changed)
	{
		$this->changed = $changed;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getWall()
	{
		return $this->wall;
	}

	/**
	 * @param bool $wall
	 * Set
	 */
	public function setWall(bool $wall)
	{
		$this->wall = $wall;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getPrivate()
	{
		return $this->private;
	}

	/**
	 * @param bool $private
	 * Set
	 */
	public function setPrivate(bool $private)
	{
		$this->private = $private;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getPubmail()
	{
		return $this->pubmail;
	}

	/**
	 * @param bool $pubmail
	 * Set
	 */
	public function setPubmail(bool $pubmail)
	{
		$this->pubmail = $pubmail;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getModerated()
	{
		return $this->moderated;
	}

	/**
	 * @param bool $moderated
	 * Set
	 */
	public function setModerated(bool $moderated)
	{
		$this->moderated = $moderated;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getVisible()
	{
		return $this->visible;
	}

	/**
	 * @param bool $visible
	 * Set
	 */
	public function setVisible(bool $visible)
	{
		$this->visible = $visible;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getStarred()
	{
		return $this->starred;
	}

	/**
	 * @param bool $starred
	 * Set
	 */
	public function setStarred(bool $starred)
	{
		$this->starred = $starred;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getIgnored()
	{
		return $this->ignored;
	}

	/**
	 * @param bool $ignored
	 * Set
	 */
	public function setIgnored(bool $ignored)
	{
		$this->ignored = $ignored;
	}

	/**
	 * @return string
	 * Get Post type (personal note, bookmark, ...)
	 */
	public function getPostType()
	{
		return $this->postType;
	}

	/**
	 * @param string $postType
	 * Set Post type (personal note, bookmark, ...)
	 */
	public function setPostType(string $postType)
	{
		$this->postType = $postType;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getUnseen()
	{
		return $this->unseen;
	}

	/**
	 * @param bool $unseen
	 * Set
	 */
	public function setUnseen(bool $unseen)
	{
		$this->unseen = $unseen;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getDeleted()
	{
		return $this->deleted;
	}

	/**
	 * @param bool $deleted
	 * Set
	 */
	public function setDeleted(bool $deleted)
	{
		$this->deleted = $deleted;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getOrigin()
	{
		return $this->origin;
	}

	/**
	 * @param bool $origin
	 * Set
	 */
	public function setOrigin(bool $origin)
	{
		$this->origin = $origin;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getForumMode()
	{
		return $this->forumMode;
	}

	/**
	 * @param string $forumMode
	 * Set
	 */
	public function setForumMode(string $forumMode)
	{
		$this->forumMode = $forumMode;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getMention()
	{
		return $this->mention;
	}

	/**
	 * @param bool $mention
	 * Set
	 */
	public function setMention(bool $mention)
	{
		$this->mention = $mention;
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
	 * @return bool
	 * Get
	 */
	public function getBookmark()
	{
		return $this->bookmark;
	}

	/**
	 * @param bool $bookmark
	 * Set
	 */
	public function setBookmark(bool $bookmark)
	{
		$this->bookmark = $bookmark;
	}
}
