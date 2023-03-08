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

namespace Friendica\Contact\LocalRelationship\Entity;

use Friendica\Core\Protocol;
use Friendica\Model\Contact;

/**
 * @property-read int    $userId
 * @property-read int    $contactId
 * @property-read int    $uriId
 * @property-read bool   $blocked
 * @property-read bool   $ignored
 * @property-read bool   $collapsed
 * @property-read bool   $hidden
 * @property-read bool   $pending
 * @property-read int    $rel
 * @property-read string $info
 * @property-read bool   $notifyNewPosts
 * @property-read bool   $isRemoteSelf
 * @property-read int    $fetchFurtherInformation
 * @property-read string $ffiKeywordDenylist
 * @property-read bool   $subhub
 * @property-read string $hubVerify
 * @property-read string $protocol
 * @property-read int    $rating
 * @property-read int    $priority
 */
class LocalRelationship extends \Friendica\BaseEntity
{
	/** @var int */
	protected $userId;
	/** @var int */
	protected $contactId;
	/** @var bool */
	protected $blocked;
	/** @var bool */
	protected $ignored;
	/** @var bool */
	protected $collapsed;
	/** @var bool */
	protected $hidden;
	/** @var bool */
	protected $pending;
	/** @var int */
	protected $rel;
	/** @var string */
	protected $info;
	/** @var bool */
	protected $notifyNewPosts;
	/** @var bool */
	protected $isRemoteSelf;
	/** @var int */
	protected $fetchFurtherInformation;
	/** @var string */
	protected $ffiKeywordDenylist;
	/** @var bool */
	protected $subhub;
	/** @var string */
	protected $hubVerify;
	/** @var string */
	protected $protocol;
	/** @var int */
	protected $rating;
	/** @var int */
	protected $priority;

	public function __construct(int $userId, int $contactId, bool $blocked = false, bool $ignored = false, bool $collapsed = false, bool $hidden = false, bool $pending = false, int $rel = Contact::NOTHING, string $info = '', bool $notifyNewPosts = false, bool $isRemoteSelf = false, int $fetchFurtherInformation = 0, string $ffiKeywordDenylist = '', bool $subhub = false, string $hubVerify = '', string $protocol = Protocol::PHANTOM, ?int $rating = null, ?int $priority = null)
	{
		$this->userId                  = $userId;
		$this->contactId               = $contactId;
		$this->blocked                 = $blocked;
		$this->ignored                 = $ignored;
		$this->collapsed               = $collapsed;
		$this->hidden                  = $hidden;
		$this->pending                 = $pending;
		$this->rel                     = $rel;
		$this->info                    = $info;
		$this->notifyNewPosts          = $notifyNewPosts;
		$this->isRemoteSelf            = $isRemoteSelf;
		$this->fetchFurtherInformation = $fetchFurtherInformation;
		$this->ffiKeywordDenylist      = $ffiKeywordDenylist;
		$this->subhub                  = $subhub;
		$this->hubVerify               = $hubVerify;
		$this->protocol                = $protocol;
		$this->rating                  = $rating;
		$this->priority                = $priority;
	}

	public function addFollow()
	{
		$this->rel = in_array($this->rel, [Contact::FOLLOWER, Contact::FRIEND]) ? Contact::FRIEND : Contact::SHARING;
	}

	public function removeFollow()
	{
		$this->rel = in_array($this->rel, [Contact::FOLLOWER, Contact::FRIEND]) ? Contact::FOLLOWER : Contact::NOTHING;
	}

	public function addFollower()
	{
		$this->rel = in_array($this->rel, [Contact::SHARING, Contact::FRIEND]) ? Contact::FRIEND : Contact::FOLLOWER;
	}

	public function removeFollower()
	{
		$this->rel = in_array($this->rel, [Contact::SHARING, Contact::FRIEND]) ? Contact::SHARING : Contact::NOTHING;
	}
}
