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
 * Entity class for table contact
 *
 * contact table
 */
class Contact extends BaseEntity
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
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of last contact update
	 */
	private $updated = '0001-01-01 00:00:00';

	/**
	 * @var bool
	 * 1 if the contact is the user him/her self
	 */
	private $self = '0';

	/**
	 * @var bool
	 */
	private $remoteSelf = '0';

	/**
	 * @var string
	 * The kind of the relation between the user and the contact
	 */
	private $rel = '0';

	/**
	 * @var bool
	 */
	private $duplex = '0';

	/**
	 * @var string
	 * Network of the contact
	 */
	private $network = '';

	/**
	 * @var string
	 * Protocol of the contact
	 */
	private $protocol = '';

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
	 */
	private $location = '';

	/**
	 * @var string
	 */
	private $about;

	/**
	 * @var string
	 * public keywords (interests) of the contact
	 */
	private $keywords;

	/**
	 * @var string
	 */
	private $gender = '';

	/**
	 * @var string
	 */
	private $xmpp = '';

	/**
	 * @var string
	 */
	private $attag = '';

	/**
	 * @var string
	 */
	private $avatar = '';

	/**
	 * @var string
	 * Link to the profile photo of the contact
	 */
	private $photo = '';

	/**
	 * @var string
	 * Link to the profile photo (thumb size)
	 */
	private $thumb = '';

	/**
	 * @var string
	 * Link to the profile photo (micro size)
	 */
	private $micro = '';

	/**
	 * @var string
	 */
	private $sitePubkey;

	/**
	 * @var string
	 */
	private $issuedId = '';

	/**
	 * @var string
	 */
	private $dfrnId = '';

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
	private $addr = '';

	/**
	 * @var string
	 */
	private $alias = '';

	/**
	 * @var string
	 * RSA public key 4096 bit
	 */
	private $pubkey;

	/**
	 * @var string
	 * RSA private key 4096 bit
	 */
	private $prvkey;

	/**
	 * @var string
	 */
	private $batch = '';

	/**
	 * @var string
	 */
	private $request;

	/**
	 * @var string
	 */
	private $notify;

	/**
	 * @var string
	 */
	private $poll;

	/**
	 * @var string
	 */
	private $confirm;

	/**
	 * @var string
	 */
	private $poco;

	/**
	 * @var bool
	 */
	private $aesAllow = '0';

	/**
	 * @var bool
	 */
	private $retAes = '0';

	/**
	 * @var bool
	 */
	private $usehub = '0';

	/**
	 * @var bool
	 */
	private $subhub = '0';

	/**
	 * @var string
	 */
	private $hubVerify = '';

	/**
	 * @var string
	 * Date of the last try to update the contact info
	 */
	private $lastUpdate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of the last successful contact update
	 */
	private $successUpdate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of the last failed update
	 */
	private $failureUpdate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $nameDate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $uriDate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $avatarDate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $termDate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * date of the last post
	 */
	private $lastItem = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $priority = '0';

	/**
	 * @var bool
	 * Node-wide block status
	 */
	private $blocked = '1';

	/**
	 * @var string
	 * Node-wide block reason
	 */
	private $blockReason;

	/**
	 * @var bool
	 * posts of the contact are readonly
	 */
	private $readonly = '0';

	/**
	 * @var bool
	 */
	private $writable = '0';

	/**
	 * @var bool
	 * contact is a forum
	 */
	private $forum = '0';

	/**
	 * @var bool
	 * contact is a private group
	 */
	private $prv = '0';

	/**
	 * @var string
	 */
	private $contactType = '0';

	/**
	 * @var bool
	 */
	private $hidden = '0';

	/**
	 * @var bool
	 */
	private $archive = '0';

	/**
	 * @var bool
	 */
	private $pending = '1';

	/**
	 * @var bool
	 * Contact has been deleted
	 */
	private $deleted = '0';

	/**
	 * @var string
	 */
	private $rating = '0';

	/**
	 * @var bool
	 * Contact prefers to not be searchable
	 */
	private $unsearchable = '0';

	/**
	 * @var bool
	 * Contact posts sensitive content
	 */
	private $sensitive = '0';

	/**
	 * @var string
	 * baseurl of the contact
	 */
	private $baseurl = '';

	/**
	 * @var string
	 */
	private $reason;

	/**
	 * @var string
	 */
	private $closeness = '99';

	/**
	 * @var string
	 */
	private $info;

	/**
	 * @var int
	 * Deprecated
	 */
	private $profileId;

	/**
	 * @var string
	 */
	private $bdyear = '';

	/**
	 * @var string
	 */
	private $bd = '0001-01-01';

	/**
	 * @var bool
	 */
	private $notifyNewPosts = '0';

	/**
	 * @var string
	 */
	private $fetchFurtherInformation = '0';

	/**
	 * @var string
	 */
	private $ffiKeywordBlacklist;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'created' => $this->created,
			'updated' => $this->updated,
			'self' => $this->self,
			'remote_self' => $this->remoteSelf,
			'rel' => $this->rel,
			'duplex' => $this->duplex,
			'network' => $this->network,
			'protocol' => $this->protocol,
			'name' => $this->name,
			'nick' => $this->nick,
			'location' => $this->location,
			'about' => $this->about,
			'keywords' => $this->keywords,
			'gender' => $this->gender,
			'xmpp' => $this->xmpp,
			'attag' => $this->attag,
			'avatar' => $this->avatar,
			'photo' => $this->photo,
			'thumb' => $this->thumb,
			'micro' => $this->micro,
			'site-pubkey' => $this->sitePubkey,
			'issued-id' => $this->issuedId,
			'dfrn-id' => $this->dfrnId,
			'url' => $this->url,
			'nurl' => $this->nurl,
			'addr' => $this->addr,
			'alias' => $this->alias,
			'pubkey' => $this->pubkey,
			'prvkey' => $this->prvkey,
			'batch' => $this->batch,
			'request' => $this->request,
			'notify' => $this->notify,
			'poll' => $this->poll,
			'confirm' => $this->confirm,
			'poco' => $this->poco,
			'aes_allow' => $this->aesAllow,
			'ret-aes' => $this->retAes,
			'usehub' => $this->usehub,
			'subhub' => $this->subhub,
			'hub-verify' => $this->hubVerify,
			'last-update' => $this->lastUpdate,
			'success_update' => $this->successUpdate,
			'failure_update' => $this->failureUpdate,
			'name-date' => $this->nameDate,
			'uri-date' => $this->uriDate,
			'avatar-date' => $this->avatarDate,
			'term-date' => $this->termDate,
			'last-item' => $this->lastItem,
			'priority' => $this->priority,
			'blocked' => $this->blocked,
			'block_reason' => $this->blockReason,
			'readonly' => $this->readonly,
			'writable' => $this->writable,
			'forum' => $this->forum,
			'prv' => $this->prv,
			'contact-type' => $this->contactType,
			'hidden' => $this->hidden,
			'archive' => $this->archive,
			'pending' => $this->pending,
			'deleted' => $this->deleted,
			'rating' => $this->rating,
			'unsearchable' => $this->unsearchable,
			'sensitive' => $this->sensitive,
			'baseurl' => $this->baseurl,
			'reason' => $this->reason,
			'closeness' => $this->closeness,
			'info' => $this->info,
			'profile-id' => $this->profileId,
			'bdyear' => $this->bdyear,
			'bd' => $this->bd,
			'notify_new_posts' => $this->notifyNewPosts,
			'fetch_further_information' => $this->fetchFurtherInformation,
			'ffi_keyword_blacklist' => $this->ffiKeywordBlacklist,
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
	 * Get Date of last contact update
	 */
	public function getUpdated()
	{
		return $this->updated;
	}

	/**
	 * @param string $updated
	 * Set Date of last contact update
	 */
	public function setUpdated(string $updated)
	{
		$this->updated = $updated;
	}

	/**
	 * @return bool
	 * Get 1 if the contact is the user him/her self
	 */
	public function getSelf()
	{
		return $this->self;
	}

	/**
	 * @param bool $self
	 * Set 1 if the contact is the user him/her self
	 */
	public function setSelf(bool $self)
	{
		$this->self = $self;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getRemoteSelf()
	{
		return $this->remoteSelf;
	}

	/**
	 * @param bool $remoteSelf
	 * Set
	 */
	public function setRemoteSelf(bool $remoteSelf)
	{
		$this->remoteSelf = $remoteSelf;
	}

	/**
	 * @return string
	 * Get The kind of the relation between the user and the contact
	 */
	public function getRel()
	{
		return $this->rel;
	}

	/**
	 * @param string $rel
	 * Set The kind of the relation between the user and the contact
	 */
	public function setRel(string $rel)
	{
		$this->rel = $rel;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getDuplex()
	{
		return $this->duplex;
	}

	/**
	 * @param bool $duplex
	 * Set
	 */
	public function setDuplex(bool $duplex)
	{
		$this->duplex = $duplex;
	}

	/**
	 * @return string
	 * Get Network of the contact
	 */
	public function getNetwork()
	{
		return $this->network;
	}

	/**
	 * @param string $network
	 * Set Network of the contact
	 */
	public function setNetwork(string $network)
	{
		$this->network = $network;
	}

	/**
	 * @return string
	 * Get Protocol of the contact
	 */
	public function getProtocol()
	{
		return $this->protocol;
	}

	/**
	 * @param string $protocol
	 * Set Protocol of the contact
	 */
	public function setProtocol(string $protocol)
	{
		$this->protocol = $protocol;
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
	 * Get public keywords (interests) of the contact
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	/**
	 * @param string $keywords
	 * Set public keywords (interests) of the contact
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
	public function getXmpp()
	{
		return $this->xmpp;
	}

	/**
	 * @param string $xmpp
	 * Set
	 */
	public function setXmpp(string $xmpp)
	{
		$this->xmpp = $xmpp;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAttag()
	{
		return $this->attag;
	}

	/**
	 * @param string $attag
	 * Set
	 */
	public function setAttag(string $attag)
	{
		$this->attag = $attag;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}

	/**
	 * @param string $avatar
	 * Set
	 */
	public function setAvatar(string $avatar)
	{
		$this->avatar = $avatar;
	}

	/**
	 * @return string
	 * Get Link to the profile photo of the contact
	 */
	public function getPhoto()
	{
		return $this->photo;
	}

	/**
	 * @param string $photo
	 * Set Link to the profile photo of the contact
	 */
	public function setPhoto(string $photo)
	{
		$this->photo = $photo;
	}

	/**
	 * @return string
	 * Get Link to the profile photo (thumb size)
	 */
	public function getThumb()
	{
		return $this->thumb;
	}

	/**
	 * @param string $thumb
	 * Set Link to the profile photo (thumb size)
	 */
	public function setThumb(string $thumb)
	{
		$this->thumb = $thumb;
	}

	/**
	 * @return string
	 * Get Link to the profile photo (micro size)
	 */
	public function getMicro()
	{
		return $this->micro;
	}

	/**
	 * @param string $micro
	 * Set Link to the profile photo (micro size)
	 */
	public function setMicro(string $micro)
	{
		$this->micro = $micro;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSitePubkey()
	{
		return $this->sitePubkey;
	}

	/**
	 * @param string $sitePubkey
	 * Set
	 */
	public function setSitePubkey(string $sitePubkey)
	{
		$this->sitePubkey = $sitePubkey;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getIssuedId()
	{
		return $this->issuedId;
	}

	/**
	 * @param string $issuedId
	 * Set
	 */
	public function setIssuedId(string $issuedId)
	{
		$this->issuedId = $issuedId;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getDfrnId()
	{
		return $this->dfrnId;
	}

	/**
	 * @param string $dfrnId
	 * Set
	 */
	public function setDfrnId(string $dfrnId)
	{
		$this->dfrnId = $dfrnId;
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
	 * Get RSA public key 4096 bit
	 */
	public function getPubkey()
	{
		return $this->pubkey;
	}

	/**
	 * @param string $pubkey
	 * Set RSA public key 4096 bit
	 */
	public function setPubkey(string $pubkey)
	{
		$this->pubkey = $pubkey;
	}

	/**
	 * @return string
	 * Get RSA private key 4096 bit
	 */
	public function getPrvkey()
	{
		return $this->prvkey;
	}

	/**
	 * @param string $prvkey
	 * Set RSA private key 4096 bit
	 */
	public function setPrvkey(string $prvkey)
	{
		$this->prvkey = $prvkey;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getBatch()
	{
		return $this->batch;
	}

	/**
	 * @param string $batch
	 * Set
	 */
	public function setBatch(string $batch)
	{
		$this->batch = $batch;
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
	public function getPoll()
	{
		return $this->poll;
	}

	/**
	 * @param string $poll
	 * Set
	 */
	public function setPoll(string $poll)
	{
		$this->poll = $poll;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getConfirm()
	{
		return $this->confirm;
	}

	/**
	 * @param string $confirm
	 * Set
	 */
	public function setConfirm(string $confirm)
	{
		$this->confirm = $confirm;
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
	 * @return bool
	 * Get
	 */
	public function getAesAllow()
	{
		return $this->aesAllow;
	}

	/**
	 * @param bool $aesAllow
	 * Set
	 */
	public function setAesAllow(bool $aesAllow)
	{
		$this->aesAllow = $aesAllow;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getRetAes()
	{
		return $this->retAes;
	}

	/**
	 * @param bool $retAes
	 * Set
	 */
	public function setRetAes(bool $retAes)
	{
		$this->retAes = $retAes;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getUsehub()
	{
		return $this->usehub;
	}

	/**
	 * @param bool $usehub
	 * Set
	 */
	public function setUsehub(bool $usehub)
	{
		$this->usehub = $usehub;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getSubhub()
	{
		return $this->subhub;
	}

	/**
	 * @param bool $subhub
	 * Set
	 */
	public function setSubhub(bool $subhub)
	{
		$this->subhub = $subhub;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getHubVerify()
	{
		return $this->hubVerify;
	}

	/**
	 * @param string $hubVerify
	 * Set
	 */
	public function setHubVerify(string $hubVerify)
	{
		$this->hubVerify = $hubVerify;
	}

	/**
	 * @return string
	 * Get Date of the last try to update the contact info
	 */
	public function getLastUpdate()
	{
		return $this->lastUpdate;
	}

	/**
	 * @param string $lastUpdate
	 * Set Date of the last try to update the contact info
	 */
	public function setLastUpdate(string $lastUpdate)
	{
		$this->lastUpdate = $lastUpdate;
	}

	/**
	 * @return string
	 * Get Date of the last successful contact update
	 */
	public function getSuccessUpdate()
	{
		return $this->successUpdate;
	}

	/**
	 * @param string $successUpdate
	 * Set Date of the last successful contact update
	 */
	public function setSuccessUpdate(string $successUpdate)
	{
		$this->successUpdate = $successUpdate;
	}

	/**
	 * @return string
	 * Get Date of the last failed update
	 */
	public function getFailureUpdate()
	{
		return $this->failureUpdate;
	}

	/**
	 * @param string $failureUpdate
	 * Set Date of the last failed update
	 */
	public function setFailureUpdate(string $failureUpdate)
	{
		$this->failureUpdate = $failureUpdate;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNameDate()
	{
		return $this->nameDate;
	}

	/**
	 * @param string $nameDate
	 * Set
	 */
	public function setNameDate(string $nameDate)
	{
		$this->nameDate = $nameDate;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getUriDate()
	{
		return $this->uriDate;
	}

	/**
	 * @param string $uriDate
	 * Set
	 */
	public function setUriDate(string $uriDate)
	{
		$this->uriDate = $uriDate;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAvatarDate()
	{
		return $this->avatarDate;
	}

	/**
	 * @param string $avatarDate
	 * Set
	 */
	public function setAvatarDate(string $avatarDate)
	{
		$this->avatarDate = $avatarDate;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getTermDate()
	{
		return $this->termDate;
	}

	/**
	 * @param string $termDate
	 * Set
	 */
	public function setTermDate(string $termDate)
	{
		$this->termDate = $termDate;
	}

	/**
	 * @return string
	 * Get date of the last post
	 */
	public function getLastItem()
	{
		return $this->lastItem;
	}

	/**
	 * @param string $lastItem
	 * Set date of the last post
	 */
	public function setLastItem(string $lastItem)
	{
		$this->lastItem = $lastItem;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param string $priority
	 * Set
	 */
	public function setPriority(string $priority)
	{
		$this->priority = $priority;
	}

	/**
	 * @return bool
	 * Get Node-wide block status
	 */
	public function getBlocked()
	{
		return $this->blocked;
	}

	/**
	 * @param bool $blocked
	 * Set Node-wide block status
	 */
	public function setBlocked(bool $blocked)
	{
		$this->blocked = $blocked;
	}

	/**
	 * @return string
	 * Get Node-wide block reason
	 */
	public function getBlockReason()
	{
		return $this->blockReason;
	}

	/**
	 * @param string $blockReason
	 * Set Node-wide block reason
	 */
	public function setBlockReason(string $blockReason)
	{
		$this->blockReason = $blockReason;
	}

	/**
	 * @return bool
	 * Get posts of the contact are readonly
	 */
	public function getReadonly()
	{
		return $this->readonly;
	}

	/**
	 * @param bool $readonly
	 * Set posts of the contact are readonly
	 */
	public function setReadonly(bool $readonly)
	{
		$this->readonly = $readonly;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getWritable()
	{
		return $this->writable;
	}

	/**
	 * @param bool $writable
	 * Set
	 */
	public function setWritable(bool $writable)
	{
		$this->writable = $writable;
	}

	/**
	 * @return bool
	 * Get contact is a forum
	 */
	public function getForum()
	{
		return $this->forum;
	}

	/**
	 * @param bool $forum
	 * Set contact is a forum
	 */
	public function setForum(bool $forum)
	{
		$this->forum = $forum;
	}

	/**
	 * @return bool
	 * Get contact is a private group
	 */
	public function getPrv()
	{
		return $this->prv;
	}

	/**
	 * @param bool $prv
	 * Set contact is a private group
	 */
	public function setPrv(bool $prv)
	{
		$this->prv = $prv;
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
	 * Get
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * @param bool $hidden
	 * Set
	 */
	public function setHidden(bool $hidden)
	{
		$this->hidden = $hidden;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getArchive()
	{
		return $this->archive;
	}

	/**
	 * @param bool $archive
	 * Set
	 */
	public function setArchive(bool $archive)
	{
		$this->archive = $archive;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getPending()
	{
		return $this->pending;
	}

	/**
	 * @param bool $pending
	 * Set
	 */
	public function setPending(bool $pending)
	{
		$this->pending = $pending;
	}

	/**
	 * @return bool
	 * Get Contact has been deleted
	 */
	public function getDeleted()
	{
		return $this->deleted;
	}

	/**
	 * @param bool $deleted
	 * Set Contact has been deleted
	 */
	public function setDeleted(bool $deleted)
	{
		$this->deleted = $deleted;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getRating()
	{
		return $this->rating;
	}

	/**
	 * @param string $rating
	 * Set
	 */
	public function setRating(string $rating)
	{
		$this->rating = $rating;
	}

	/**
	 * @return bool
	 * Get Contact prefers to not be searchable
	 */
	public function getUnsearchable()
	{
		return $this->unsearchable;
	}

	/**
	 * @param bool $unsearchable
	 * Set Contact prefers to not be searchable
	 */
	public function setUnsearchable(bool $unsearchable)
	{
		$this->unsearchable = $unsearchable;
	}

	/**
	 * @return bool
	 * Get Contact posts sensitive content
	 */
	public function getSensitive()
	{
		return $this->sensitive;
	}

	/**
	 * @param bool $sensitive
	 * Set Contact posts sensitive content
	 */
	public function setSensitive(bool $sensitive)
	{
		$this->sensitive = $sensitive;
	}

	/**
	 * @return string
	 * Get baseurl of the contact
	 */
	public function getBaseurl()
	{
		return $this->baseurl;
	}

	/**
	 * @param string $baseurl
	 * Set baseurl of the contact
	 */
	public function setBaseurl(string $baseurl)
	{
		$this->baseurl = $baseurl;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getReason()
	{
		return $this->reason;
	}

	/**
	 * @param string $reason
	 * Set
	 */
	public function setReason(string $reason)
	{
		$this->reason = $reason;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCloseness()
	{
		return $this->closeness;
	}

	/**
	 * @param string $closeness
	 * Set
	 */
	public function setCloseness(string $closeness)
	{
		$this->closeness = $closeness;
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
	 * @return int
	 * Get Deprecated
	 */
	public function getProfileId()
	{
		return $this->profileId;
	}

	/**
	 * @param int $profileId
	 * Set Deprecated
	 */
	public function setProfileId(int $profileId)
	{
		$this->profileId = $profileId;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getBdyear()
	{
		return $this->bdyear;
	}

	/**
	 * @param string $bdyear
	 * Set
	 */
	public function setBdyear(string $bdyear)
	{
		$this->bdyear = $bdyear;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getBd()
	{
		return $this->bd;
	}

	/**
	 * @param string $bd
	 * Set
	 */
	public function setBd(string $bd)
	{
		$this->bd = $bd;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getNotifyNewPosts()
	{
		return $this->notifyNewPosts;
	}

	/**
	 * @param bool $notifyNewPosts
	 * Set
	 */
	public function setNotifyNewPosts(bool $notifyNewPosts)
	{
		$this->notifyNewPosts = $notifyNewPosts;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getFetchFurtherInformation()
	{
		return $this->fetchFurtherInformation;
	}

	/**
	 * @param string $fetchFurtherInformation
	 * Set
	 */
	public function setFetchFurtherInformation(string $fetchFurtherInformation)
	{
		$this->fetchFurtherInformation = $fetchFurtherInformation;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getFfiKeywordBlacklist()
	{
		return $this->ffiKeywordBlacklist;
	}

	/**
	 * @param string $ffiKeywordBlacklist
	 * Set
	 */
	public function setFfiKeywordBlacklist(string $ffiKeywordBlacklist)
	{
		$this->ffiKeywordBlacklist = $ffiKeywordBlacklist;
	}
}
