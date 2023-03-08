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

namespace Friendica\Navigation\Notifications\Entity;

use DateTime;
use Friendica\BaseEntity;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Psr\Http\Message\UriInterface;

/**
 * @property-read $type
 * @property-read $name
 * @property-read $url
 * @property-read $photo
 * @property-read $date
 * @property-read $msg
 * @property-read $uid
 * @property-read $link
 * @property-read $itemId
 * @property-read $parent
 * @property-read $seen
 * @property-read $verb
 * @property-read $otype
 * @property-read $name_cache
 * @property-read $msg_cache
 * @property-read $uriId
 * @property-read $parentUriId
 * @property-read $id
 *
 * @deprecated since 2022.05 Use \Friendica\Navigation\Notifications\Entity\Notification instead
 */
class Notify extends BaseEntity
{
	/** @var int */
	protected $type;
	/** @var string */
	protected $name;
	/** @var UriInterface */
	protected $url;
	/** @var UriInterface */
	protected $photo;
	/** @var DateTime */
	protected $date;
	/** @var string */
	protected $msg;
	/** @var int */
	protected $uid;
	/** @var UriInterface */
	protected $link;
	/** @var int */
	protected $itemId;
	/** @var int */
	protected $parent;
	/** @var bool */
	protected $seen;
	/** @var string */
	protected $verb;
	/** @var string */
	protected $otype;
	/** @var string */
	protected $name_cache;
	/** @var string */
	protected $msg_cache;
	/** @var int|null */
	protected $uriId;
	/** @var int|null */
	protected $parentUriId;
	/** @var int */
	protected $id;

	public function __construct(int $type, string $name, UriInterface $url, UriInterface $photo, DateTime $date, int $uid, UriInterface $link, bool $seen, string $verb, string $otype, string $name_cache, string $msg = null, string $msg_cache = null, int $itemId = null, int $uriId = null, int $parent = null, ?int $parentUriId = null, ?int $id = null)
	{
		$this->type        = $type;
		$this->name        = $name;
		$this->url         = $url;
		$this->photo       = $photo;
		$this->date        = $date;
		$this->msg         = $msg;
		$this->uid         = $uid;
		$this->link        = $link;
		$this->itemId      = $itemId;
		$this->parent      = $parent;
		$this->seen        = $seen;
		$this->verb        = $verb;
		$this->otype       = $otype;
		$this->name_cache  = $name_cache;
		$this->msg_cache   = $msg_cache;
		$this->uriId       = $uriId;
		$this->parentUriId = $parentUriId;
		$this->id          = $id;
	}

	public function setSeen()
	{
		$this->seen = true;
	}

	public function updateMsgFromPreamble($epreamble)
	{
		$this->msg       = Renderer::replaceMacros($epreamble, ['$itemlink' => $this->link->__toString()]);
		$this->msg_cache = self::formatMessage($this->name_cache, strip_tags(BBCode::convert($this->msg)));
	}

	/**
	 * Formats a notification message with the notification author
	 *
	 * Replace the name with {0} but ensure to make that only once. The {0} is used
	 * later and prints the name in bold.
	 *
	 * @param string $name
	 * @param string $message
	 *
	 * @return string Formatted message
	 */
	public static function formatMessage(string $name, string $message): string
	{
		return str_replace('{0}', '<span class="contactname">' . strip_tags(BBCode::convert($name)) . '</span>', $message);
	}
}
