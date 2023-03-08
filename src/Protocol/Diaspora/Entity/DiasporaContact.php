<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
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
 */

namespace Friendica\Protocol\Diaspora\Entity;

use Psr\Http\Message\UriInterface;

/**
 * @property-read $uriId
 * @property-read $url
 * @property-read $guid
 * @property-read $addr
 * @property-read $alias
 * @property-read $nick
 * @property-read $name
 * @property-read $givenName
 * @property-read $familyName
 * @property-read $photo
 * @property-read $photoMedium
 * @property-read $photoSmall
 * @property-read $batch
 * @property-read $notify
 * @property-read $poll
 * @property-read $subscribe
 * @property-read $searchable
 * @property-read $pubKey
 * @property-read $baseurl
 * @property-read $gsid
 * @property-read $created
 * @property-read $updated
 * @property-read $interacting_count
 * @property-read $interacted_count
 * @property-read $post_count
 */
class DiasporaContact extends \Friendica\BaseEntity
{
	/** @var int */
	protected $uriId;
	/** @var UriInterface */
	protected $url;
	/** @var string */
	protected $guid;
	/** @var string */
	protected $addr;
	/** @var UriInterface */
	protected $alias;
	/** @var string */
	protected $nick;
	/** @var string */
	protected $name;
	/** @var string */
	protected $givenName;
	/** @var string */
	protected $familyName;
	/** @var UriInterface */
	protected $photo;
	/** @var UriInterface */
	protected $photoMedium;
	/** @var UriInterface */
	protected $photoSmall;
	/** @var UriInterface */
	protected $batch;
	/** @var UriInterface */
	protected $notify;
	/** @var UriInterface */
	protected $poll;
	/** @var string URL pattern string including a placeholder "{uri}" that mustn't be URL-encoded */
	protected $subscribe;
	/** @var bool */
	protected $searchable;
	/** @var string */
	protected $pubKey;
	/** @var UriInterface */
	protected $baseurl;
	/** @var int */
	protected $gsid;
	/** @var \DateTime */
	protected $created;
	/** @var \DateTime */
	protected $updated;
	/** @var int */
	protected $interacting_count;
	/** @var int */
	protected $interacted_count;
	/** @var int */
	protected $post_count;

	public function __construct(
		UriInterface $url, \DateTime $created, string $guid = null, string $addr = null, UriInterface $alias = null,
		string $nick = null, string $name = null, string $givenName = null, string $familyName = null,
		UriInterface $photo = null, UriInterface $photoMedium = null, UriInterface $photoSmall = null,
		UriInterface $batch = null, UriInterface $notify = null, UriInterface $poll = null, string $subscribe = null,
		bool $searchable = null, string $pubKey = null, UriInterface $baseurl = null, int $gsid = null,
		\DateTime $updated = null, int $interacting_count = 0, int $interacted_count = 0, int $post_count = 0, int $uriId = null
	) {
		$this->uriId             = $uriId;
		$this->url               = $url;
		$this->guid              = $guid;
		$this->addr              = $addr;
		$this->alias             = $alias;
		$this->nick              = $nick;
		$this->name              = $name;
		$this->givenName         = $givenName;
		$this->familyName        = $familyName;
		$this->photo             = $photo;
		$this->photoMedium       = $photoMedium;
		$this->photoSmall        = $photoSmall;
		$this->batch             = $batch;
		$this->notify            = $notify;
		$this->poll              = $poll;
		$this->subscribe         = $subscribe;
		$this->searchable        = $searchable;
		$this->pubKey            = $pubKey;
		$this->baseurl           = $baseurl;
		$this->gsid              = $gsid;
		$this->created           = $created;
		$this->updated           = $updated;
		$this->interacting_count = $interacting_count;
		$this->interacted_count  = $interacted_count;
		$this->post_count        = $post_count;
	}
}
