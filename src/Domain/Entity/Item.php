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
 * Entity class for table item
 *
 * Structure for all posts
 */
class Item extends BaseEntity
{
	/** @var int */
	private $id;

	/**
	 * @var string
	 * A unique identifier for this item
	 */
	private $guid = '';

	/**
	 * @var string
	 */
	private $uri = '';

	/**
	 * @var int
	 * Id of the item-uri table entry that contains the item uri
	 */
	private $uriId;

	/**
	 * @var string
	 * RIPEMD-128 hash from uri
	 */
	private $uriHash = '';

	/**
	 * @var int
	 * item.id of the parent to this item if it is a reply of some form; otherwise this must be set to the id of this item
	 */
	private $parent = '0';

	/**
	 * @var string
	 * uri of the parent to this item
	 */
	private $parentUri = '';

	/**
	 * @var int
	 * Id of the item-uri table that contains the parent uri
	 */
	private $parentUriId;

	/**
	 * @var string
	 * If the parent of this item is not the top-level item in the conversation, the uri of the immediate parent; otherwise set to parent-uri
	 */
	private $thrParent = '';

	/**
	 * @var int
	 * Id of the item-uri table that contains the thread parent uri
	 */
	private $thrParentId;

	/**
	 * @var string
	 * Creation timestamp.
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of last edit (default is created)
	 */
	private $edited = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of last comment/reply to this item
	 */
	private $commented = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * datetime
	 */
	private $received = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date that something in the conversation changed, indicating clients should fetch the conversation again
	 */
	private $changed = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $gravity = '0';

	/**
	 * @var string
	 * Network from where the item comes from
	 */
	private $network = '';

	/**
	 * @var int
	 * Link to the contact table with uid=0 of the owner of this item
	 */
	private $ownerId = '0';

	/**
	 * @var int
	 * Link to the contact table with uid=0 of the author of this item
	 */
	private $authorId = '0';

	/**
	 * @var int
	 * Id of the item-content table entry that contains the whole item content
	 */
	private $icid;

	/**
	 * @var int
	 * Id of the item-activity table entry that contains the activity data
	 */
	private $iaid;

	/**
	 * @var string
	 */
	private $extid = '';

	/**
	 * @var string
	 * Post type (personal note, bookmark, ...)
	 */
	private $postType = '0';

	/**
	 * @var bool
	 */
	private $global = '0';

	/**
	 * @var bool
	 * distribution is restricted
	 */
	private $private = '0';

	/**
	 * @var bool
	 */
	private $visible = '0';

	/**
	 * @var bool
	 */
	private $moderated = '0';

	/**
	 * @var bool
	 * item has been deleted
	 */
	private $deleted = '0';

	/**
	 * @var int
	 * Owner id which owns this copy of the item
	 */
	private $uid = '0';

	/**
	 * @var int
	 * contact.id
	 */
	private $contactId = '0';

	/**
	 * @var bool
	 * This item was posted to the wall of uid
	 */
	private $wall = '0';

	/**
	 * @var bool
	 * item originated at this site
	 */
	private $origin = '0';

	/**
	 * @var bool
	 */
	private $pubmail = '0';

	/**
	 * @var bool
	 * item has been favourited
	 */
	private $starred = '0';

	/**
	 * @var bool
	 * item has not been seen
	 */
	private $unseen = '1';

	/**
	 * @var bool
	 * The owner of this item was mentioned in it
	 */
	private $mention = '0';

	/**
	 * @var string
	 */
	private $forumMode = '0';

	/**
	 * @var int
	 * ID of the permission set of this post
	 */
	private $psid;

	/**
	 * @var string
	 * Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type
	 */
	private $resourceId = '';

	/**
	 * @var int
	 * Used to link to the event.id
	 */
	private $eventId = '0';

	/**
	 * @var string
	 * JSON structure representing attachments to this item
	 */
	private $attach;

	/**
	 * @var string
	 * Deprecated
	 */
	private $allowCid;

	/**
	 * @var string
	 * Deprecated
	 */
	private $allowGid;

	/**
	 * @var string
	 * Deprecated
	 */
	private $denyCid;

	/**
	 * @var string
	 * Deprecated
	 */
	private $denyGid;

	/**
	 * @var string
	 * Deprecated
	 */
	private $postopts;

	/**
	 * @var string
	 * Deprecated
	 */
	private $inform;

	/**
	 * @var string
	 * Deprecated
	 */
	private $type;

	/**
	 * @var bool
	 * Deprecated
	 */
	private $bookmark;

	/**
	 * @var string
	 * Deprecated
	 */
	private $file;

	/**
	 * @var string
	 * Deprecated
	 */
	private $location;

	/**
	 * @var string
	 * Deprecated
	 */
	private $coord;

	/**
	 * @var string
	 * Deprecated
	 */
	private $tag;

	/**
	 * @var string
	 * Deprecated
	 */
	private $plink;

	/**
	 * @var string
	 * Deprecated
	 */
	private $title;

	/**
	 * @var string
	 * Deprecated
	 */
	private $contentWarning;

	/**
	 * @var string
	 * Deprecated
	 */
	private $body;

	/**
	 * @var string
	 * Deprecated
	 */
	private $app;

	/**
	 * @var string
	 * Deprecated
	 */
	private $verb;

	/**
	 * @var string
	 * Deprecated
	 */
	private $objectType;

	/**
	 * @var string
	 * Deprecated
	 */
	private $object;

	/**
	 * @var string
	 * Deprecated
	 */
	private $targetType;

	/**
	 * @var string
	 * Deprecated
	 */
	private $target;

	/**
	 * @var string
	 * Deprecated
	 */
	private $authorName;

	/**
	 * @var string
	 * Deprecated
	 */
	private $authorLink;

	/**
	 * @var string
	 * Deprecated
	 */
	private $authorAvatar;

	/**
	 * @var string
	 * Deprecated
	 */
	private $ownerName;

	/**
	 * @var string
	 * Deprecated
	 */
	private $ownerLink;

	/**
	 * @var string
	 * Deprecated
	 */
	private $ownerAvatar;

	/**
	 * @var string
	 * Deprecated
	 */
	private $renderedHash;

	/**
	 * @var string
	 * Deprecated
	 */
	private $renderedHtml;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'guid' => $this->guid,
			'uri' => $this->uri,
			'uri-id' => $this->uriId,
			'uri-hash' => $this->uriHash,
			'parent' => $this->parent,
			'parent-uri' => $this->parentUri,
			'parent-uri-id' => $this->parentUriId,
			'thr-parent' => $this->thrParent,
			'thr-parent-id' => $this->thrParentId,
			'created' => $this->created,
			'edited' => $this->edited,
			'commented' => $this->commented,
			'received' => $this->received,
			'changed' => $this->changed,
			'gravity' => $this->gravity,
			'network' => $this->network,
			'owner-id' => $this->ownerId,
			'author-id' => $this->authorId,
			'icid' => $this->icid,
			'iaid' => $this->iaid,
			'extid' => $this->extid,
			'post-type' => $this->postType,
			'global' => $this->global,
			'private' => $this->private,
			'visible' => $this->visible,
			'moderated' => $this->moderated,
			'deleted' => $this->deleted,
			'uid' => $this->uid,
			'contact-id' => $this->contactId,
			'wall' => $this->wall,
			'origin' => $this->origin,
			'pubmail' => $this->pubmail,
			'starred' => $this->starred,
			'unseen' => $this->unseen,
			'mention' => $this->mention,
			'forum_mode' => $this->forumMode,
			'psid' => $this->psid,
			'resource-id' => $this->resourceId,
			'event-id' => $this->eventId,
			'attach' => $this->attach,
			'allow_cid' => $this->allowCid,
			'allow_gid' => $this->allowGid,
			'deny_cid' => $this->denyCid,
			'deny_gid' => $this->denyGid,
			'postopts' => $this->postopts,
			'inform' => $this->inform,
			'type' => $this->type,
			'bookmark' => $this->bookmark,
			'file' => $this->file,
			'location' => $this->location,
			'coord' => $this->coord,
			'tag' => $this->tag,
			'plink' => $this->plink,
			'title' => $this->title,
			'content-warning' => $this->contentWarning,
			'body' => $this->body,
			'app' => $this->app,
			'verb' => $this->verb,
			'object-type' => $this->objectType,
			'object' => $this->object,
			'target-type' => $this->targetType,
			'target' => $this->target,
			'author-name' => $this->authorName,
			'author-link' => $this->authorLink,
			'author-avatar' => $this->authorAvatar,
			'owner-name' => $this->ownerName,
			'owner-link' => $this->ownerLink,
			'owner-avatar' => $this->ownerAvatar,
			'rendered-hash' => $this->renderedHash,
			'rendered-html' => $this->renderedHtml,
		];
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get Thread
	 *
	 * @return Thread
	 */
	public function getThread()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for iid is not implemented yet');
	}

	/**
	 * @return string
	 * Get A unique identifier for this item
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set A unique identifier for this item
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
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
	 * @return int
	 * Get Id of the item-uri table entry that contains the item uri
	 */
	public function getUriId()
	{
		return $this->uriId;
	}

	/**
	 * @param int $uriId
	 * Set Id of the item-uri table entry that contains the item uri
	 */
	public function setUriId(int $uriId)
	{
		$this->uriId = $uriId;
	}

	/**
	 * Get \ItemUri
	 *
	 * @return \ItemUri
	 */
	public function getItemUri()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get RIPEMD-128 hash from uri
	 */
	public function getUriHash()
	{
		return $this->uriHash;
	}

	/**
	 * @param string $uriHash
	 * Set RIPEMD-128 hash from uri
	 */
	public function setUriHash(string $uriHash)
	{
		$this->uriHash = $uriHash;
	}

	/**
	 * @return int
	 * Get item.id of the parent to this item if it is a reply of some form; otherwise this must be set to the id of this item
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @param int $parent
	 * Set item.id of the parent to this item if it is a reply of some form; otherwise this must be set to the id of this item
	 */
	public function setParent(int $parent)
	{
		$this->parent = $parent;
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
	 * @return string
	 * Get uri of the parent to this item
	 */
	public function getParentUri()
	{
		return $this->parentUri;
	}

	/**
	 * @param string $parentUri
	 * Set uri of the parent to this item
	 */
	public function setParentUri(string $parentUri)
	{
		$this->parentUri = $parentUri;
	}

	/**
	 * @return int
	 * Get Id of the item-uri table that contains the parent uri
	 */
	public function getParentUriId()
	{
		return $this->parentUriId;
	}

	/**
	 * @param int $parentUriId
	 * Set Id of the item-uri table that contains the parent uri
	 */
	public function setParentUriId(int $parentUriId)
	{
		$this->parentUriId = $parentUriId;
	}

	/**
	 * @return string
	 * Get If the parent of this item is not the top-level item in the conversation, the uri of the immediate parent; otherwise set to parent-uri
	 */
	public function getThrParent()
	{
		return $this->thrParent;
	}

	/**
	 * @param string $thrParent
	 * Set If the parent of this item is not the top-level item in the conversation, the uri of the immediate parent; otherwise set to parent-uri
	 */
	public function setThrParent(string $thrParent)
	{
		$this->thrParent = $thrParent;
	}

	/**
	 * @return int
	 * Get Id of the item-uri table that contains the thread parent uri
	 */
	public function getThrParentId()
	{
		return $this->thrParentId;
	}

	/**
	 * @param int $thrParentId
	 * Set Id of the item-uri table that contains the thread parent uri
	 */
	public function setThrParentId(int $thrParentId)
	{
		$this->thrParentId = $thrParentId;
	}

	/**
	 * @return string
	 * Get Creation timestamp.
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set Creation timestamp.
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get Date of last edit (default is created)
	 */
	public function getEdited()
	{
		return $this->edited;
	}

	/**
	 * @param string $edited
	 * Set Date of last edit (default is created)
	 */
	public function setEdited(string $edited)
	{
		$this->edited = $edited;
	}

	/**
	 * @return string
	 * Get Date of last comment/reply to this item
	 */
	public function getCommented()
	{
		return $this->commented;
	}

	/**
	 * @param string $commented
	 * Set Date of last comment/reply to this item
	 */
	public function setCommented(string $commented)
	{
		$this->commented = $commented;
	}

	/**
	 * @return string
	 * Get datetime
	 */
	public function getReceived()
	{
		return $this->received;
	}

	/**
	 * @param string $received
	 * Set datetime
	 */
	public function setReceived(string $received)
	{
		$this->received = $received;
	}

	/**
	 * @return string
	 * Get Date that something in the conversation changed, indicating clients should fetch the conversation again
	 */
	public function getChanged()
	{
		return $this->changed;
	}

	/**
	 * @param string $changed
	 * Set Date that something in the conversation changed, indicating clients should fetch the conversation again
	 */
	public function setChanged(string $changed)
	{
		$this->changed = $changed;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getGravity()
	{
		return $this->gravity;
	}

	/**
	 * @param string $gravity
	 * Set
	 */
	public function setGravity(string $gravity)
	{
		$this->gravity = $gravity;
	}

	/**
	 * @return string
	 * Get Network from where the item comes from
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $network
	 * Set Network from where the item comes from
	 */
	public function setNetwork(string $network)
	{
		$this->network = $network;
	}

	/**
	 * @return int
	 * Get Link to the contact table with uid=0 of the owner of this item
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
	}

	/**
	 * @param int $ownerId
	 * Set Link to the contact table with uid=0 of the owner of this item
	 */
	public function setOwnerId(int $ownerId)
	{
		$this->ownerId = $ownerId;
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
	 * Get Link to the contact table with uid=0 of the author of this item
	 */
	public function getAuthorId()
	{
		return $this->authorId;
	}

	/**
	 * @param int $authorId
	 * Set Link to the contact table with uid=0 of the author of this item
	 */
	public function setAuthorId(int $authorId)
	{
		$this->authorId = $authorId;
	}

	/**
	 * @return int
	 * Get Id of the item-content table entry that contains the whole item content
	 */
	public function getIcid()
	{
		return $this->icid;
	}

	/**
	 * @param int $icid
	 * Set Id of the item-content table entry that contains the whole item content
	 */
	public function setIcid(int $icid)
	{
		$this->icid = $icid;
	}

	/**
	 * Get \ItemContent
	 *
	 * @return \ItemContent
	 */
	public function getItemContent()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return int
	 * Get Id of the item-activity table entry that contains the activity data
	 */
	public function getIaid()
	{
		return $this->iaid;
	}

	/**
	 * @param int $iaid
	 * Set Id of the item-activity table entry that contains the activity data
	 */
	public function setIaid(int $iaid)
	{
		$this->iaid = $iaid;
	}

	/**
	 * Get \ItemActivity
	 *
	 * @return \ItemActivity
	 */
	public function getItemActivity()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getExtid()
	{
		return $this->extid;
	}

	/**
	 * @param string $extid
	 * Set
	 */
	public function setExtid(string $extid)
	{
		$this->extid = $extid;
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
	public function getGlobal()
	{
		return $this->global;
	}

	/**
	 * @param bool $global
	 * Set
	 */
	public function setGlobal(bool $global)
	{
		$this->global = $global;
	}

	/**
	 * @return bool
	 * Get distribution is restricted
	 */
	public function getPrivate()
	{
		return $this->private;
	}

	/**
	 * @param bool $private
	 * Set distribution is restricted
	 */
	public function setPrivate(bool $private)
	{
		$this->private = $private;
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
	 * Get item has been deleted
	 */
	public function getDeleted()
	{
		return $this->deleted;
	}

	/**
	 * @param bool $deleted
	 * Set item has been deleted
	 */
	public function setDeleted(bool $deleted)
	{
		$this->deleted = $deleted;
	}

	/**
	 * @return int
	 * Get Owner id which owns this copy of the item
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set Owner id which owns this copy of the item
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
	 * @return bool
	 * Get This item was posted to the wall of uid
	 */
	public function getWall()
	{
		return $this->wall;
	}

	/**
	 * @param bool $wall
	 * Set This item was posted to the wall of uid
	 */
	public function setWall(bool $wall)
	{
		$this->wall = $wall;
	}

	/**
	 * @return bool
	 * Get item originated at this site
	 */
	public function getOrigin()
	{
		return $this->origin;
	}

	/**
	 * @param bool $origin
	 * Set item originated at this site
	 */
	public function setOrigin(bool $origin)
	{
		$this->origin = $origin;
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
	 * Get item has been favourited
	 */
	public function getStarred()
	{
		return $this->starred;
	}

	/**
	 * @param bool $starred
	 * Set item has been favourited
	 */
	public function setStarred(bool $starred)
	{
		$this->starred = $starred;
	}

	/**
	 * @return bool
	 * Get item has not been seen
	 */
	public function getUnseen()
	{
		return $this->unseen;
	}

	/**
	 * @param bool $unseen
	 * Set item has not been seen
	 */
	public function setUnseen(bool $unseen)
	{
		$this->unseen = $unseen;
	}

	/**
	 * @return bool
	 * Get The owner of this item was mentioned in it
	 */
	public function getMention()
	{
		return $this->mention;
	}

	/**
	 * @param bool $mention
	 * Set The owner of this item was mentioned in it
	 */
	public function setMention(bool $mention)
	{
		$this->mention = $mention;
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
	 * @return int
	 * Get ID of the permission set of this post
	 */
	public function getPsid()
	{
		return $this->psid;
	}

	/**
	 * @param int $psid
	 * Set ID of the permission set of this post
	 */
	public function setPsid(int $psid)
	{
		$this->psid = $psid;
	}

	/**
	 * Get Permissionset
	 *
	 * @return Permissionset
	 */
	public function getPermissionset()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type
	 */
	public function getResourceId()
	{
		return $this->resourceId;
	}

	/**
	 * @param string $resourceId
	 * Set Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type
	 */
	public function setResourceId(string $resourceId)
	{
		$this->resourceId = $resourceId;
	}

	/**
	 * @return int
	 * Get Used to link to the event.id
	 */
	public function getEventId()
	{
		return $this->eventId;
	}

	/**
	 * @param int $eventId
	 * Set Used to link to the event.id
	 */
	public function setEventId(int $eventId)
	{
		$this->eventId = $eventId;
	}

	/**
	 * Get Event
	 *
	 * @return Event
	 */
	public function getEvent()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get JSON structure representing attachments to this item
	 */
	public function getAttach()
	{
		return $this->attach;
	}

	/**
	 * @param string $attach
	 * Set JSON structure representing attachments to this item
	 */
	public function setAttach(string $attach)
	{
		$this->attach = $attach;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getAllowCid()
	{
		return $this->allowCid;
	}

	/**
	 * @param string $allowCid
	 * Set Deprecated
	 */
	public function setAllowCid(string $allowCid)
	{
		$this->allowCid = $allowCid;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getAllowGid()
	{
		return $this->allowGid;
	}

	/**
	 * @param string $allowGid
	 * Set Deprecated
	 */
	public function setAllowGid(string $allowGid)
	{
		$this->allowGid = $allowGid;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getDenyCid()
	{
		return $this->denyCid;
	}

	/**
	 * @param string $denyCid
	 * Set Deprecated
	 */
	public function setDenyCid(string $denyCid)
	{
		$this->denyCid = $denyCid;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getDenyGid()
	{
		return $this->denyGid;
	}

	/**
	 * @param string $denyGid
	 * Set Deprecated
	 */
	public function setDenyGid(string $denyGid)
	{
		$this->denyGid = $denyGid;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getPostopts()
	{
		return $this->postopts;
	}

	/**
	 * @param string $postopts
	 * Set Deprecated
	 */
	public function setPostopts(string $postopts)
	{
		$this->postopts = $postopts;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getInform()
	{
		return $this->inform;
	}

	/**
	 * @param string $inform
	 * Set Deprecated
	 */
	public function setInform(string $inform)
	{
		$this->inform = $inform;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * Set Deprecated
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return bool
	 * Get Deprecated
	 */
	public function getBookmark()
	{
		return $this->bookmark;
	}

	/**
	 * @param bool $bookmark
	 * Set Deprecated
	 */
	public function setBookmark(bool $bookmark)
	{
		$this->bookmark = $bookmark;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param string $file
	 * Set Deprecated
	 */
	public function setFile(string $file)
	{
		$this->file = $file;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @param string $location
	 * Set Deprecated
	 */
	public function setLocation(string $location)
	{
		$this->location = $location;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getCoord()
	{
		return $this->coord;
	}

	/**
	 * @param string $coord
	 * Set Deprecated
	 */
	public function setCoord(string $coord)
	{
		$this->coord = $coord;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getTag()
	{
		return $this->tag;
	}

	/**
	 * @param string $tag
	 * Set Deprecated
	 */
	public function setTag(string $tag)
	{
		$this->tag = $tag;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getPlink()
	{
		return $this->plink;
	}

	/**
	 * @param string $plink
	 * Set Deprecated
	 */
	public function setPlink(string $plink)
	{
		$this->plink = $plink;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * Set Deprecated
	 */
	public function setTitle(string $title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getContentWarning()
	{
		return $this->contentWarning;
	}

	/**
	 * @param string $contentWarning
	 * Set Deprecated
	 */
	public function setContentWarning(string $contentWarning)
	{
		$this->contentWarning = $contentWarning;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $body
	 * Set Deprecated
	 */
	public function setBody(string $body)
	{
		$this->body = $body;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * @param string $app
	 * Set Deprecated
	 */
	public function setApp(string $app)
	{
		$this->app = $app;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getVerb()
	{
		return $this->verb;
	}

	/**
	 * @param string $verb
	 * Set Deprecated
	 */
	public function setVerb(string $verb)
	{
		$this->verb = $verb;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getObjectType()
	{
		return $this->objectType;
	}

	/**
	 * @param string $objectType
	 * Set Deprecated
	 */
	public function setObjectType(string $objectType)
	{
		$this->objectType = $objectType;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getObject()
	{
		return $this->object;
	}

	/**
	 * @param string $object
	 * Set Deprecated
	 */
	public function setObject(string $object)
	{
		$this->object = $object;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getTargetType()
	{
		return $this->targetType;
	}

	/**
	 * @param string $targetType
	 * Set Deprecated
	 */
	public function setTargetType(string $targetType)
	{
		$this->targetType = $targetType;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @param string $target
	 * Set Deprecated
	 */
	public function setTarget(string $target)
	{
		$this->target = $target;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getAuthorName()
	{
		return $this->authorName;
	}

	/**
	 * @param string $authorName
	 * Set Deprecated
	 */
	public function setAuthorName(string $authorName)
	{
		$this->authorName = $authorName;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getAuthorLink()
	{
		return $this->authorLink;
	}

	/**
	 * @param string $authorLink
	 * Set Deprecated
	 */
	public function setAuthorLink(string $authorLink)
	{
		$this->authorLink = $authorLink;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getAuthorAvatar()
	{
		return $this->authorAvatar;
	}

	/**
	 * @param string $authorAvatar
	 * Set Deprecated
	 */
	public function setAuthorAvatar(string $authorAvatar)
	{
		$this->authorAvatar = $authorAvatar;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getOwnerName()
	{
		return $this->ownerName;
	}

	/**
	 * @param string $ownerName
	 * Set Deprecated
	 */
	public function setOwnerName(string $ownerName)
	{
		$this->ownerName = $ownerName;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getOwnerLink()
	{
		return $this->ownerLink;
	}

	/**
	 * @param string $ownerLink
	 * Set Deprecated
	 */
	public function setOwnerLink(string $ownerLink)
	{
		$this->ownerLink = $ownerLink;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getOwnerAvatar()
	{
		return $this->ownerAvatar;
	}

	/**
	 * @param string $ownerAvatar
	 * Set Deprecated
	 */
	public function setOwnerAvatar(string $ownerAvatar)
	{
		$this->ownerAvatar = $ownerAvatar;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getRenderedHash()
	{
		return $this->renderedHash;
	}

	/**
	 * @param string $renderedHash
	 * Set Deprecated
	 */
	public function setRenderedHash(string $renderedHash)
	{
		$this->renderedHash = $renderedHash;
	}

	/**
	 * @return string
	 * Get Deprecated
	 */
	public function getRenderedHtml()
	{
		return $this->renderedHtml;
	}

	/**
	 * @param string $renderedHtml
	 * Set Deprecated
	 */
	public function setRenderedHtml(string $renderedHtml)
	{
		$this->renderedHtml = $renderedHtml;
	}
}
