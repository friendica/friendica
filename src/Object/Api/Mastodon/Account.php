<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;

/**
 * Class Account
 *
 * @see https://docs.joinmastodon.org/entities/account
 */
class Account extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $username;
	/** @var string */
	protected $acct;
	/** @var string */
	protected $display_name;
	/** @var bool */
	protected $locked;
	/** @var bool|null */
	protected $bot = null;
	/** @var bool */
	protected $discoverable;
	/** @var bool */
	protected $group;
	/** @var string|null (Datetime) */
	protected $created_at;
	/** @var string */
	protected $note;
	/** @var string (URL)*/
	protected $url;
	/** @var string (URL) */
	protected $avatar;
	/** @var string (URL) */
	protected $avatar_static;
	/** @var string (URL) */
	protected $header;
	/** @var string (URL) */
	protected $header_static;
	/** @var int */
	protected $followers_count;
	/** @var int */
	protected $following_count;
	/** @var int */
	protected $statuses_count;
	/** @var string|null (Datetime) */
	protected $last_status_at = null;
	/** @var Emoji[] */
	protected $emojis;
	/** @var Account|null */
	protected $moved = null;
	/** @var Field[]|null */
	protected $fields = null;

	/**
	 * Creates an account record from a public contact record. Expects all contact table fields to be set.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $publicContact Full contact table record with uid = 0
	 * @param array   $apcontact     Optional full apcontact table record
	 * @param array   $userContact   Optional full contact table record with uid != 0
	 * @param array   $fcontact      Optional full fcontact table record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $publicContact, Fields $fields, array $apcontact = [], array $userContact = [], array $fcontact = [])
	{
		$this->id              = (string)$publicContact['id'];
		$this->username        = $publicContact['nick'];
		$this->acct            =
			strpos($publicContact['url'], $baseUrl->get() . '/') === 0 ?
				$publicContact['nick'] :
				$publicContact['addr'];
		$this->display_name    = $publicContact['name'];
		$this->locked          = (bool)$publicContact['manually-approve'] ?? !empty($apcontact['manually-approve']);
		$this->bot             = ($publicContact['contact-type'] == Contact::TYPE_NEWS);
		$this->discoverable    = !$publicContact['unsearchable'];
		$this->group           = ($publicContact['contact-type'] == Contact::TYPE_COMMUNITY);

		$publicContactCreated = $publicContact['created'] ?: DBA::NULL_DATETIME;
		$userContactCreated = $userContact['created'] ?? DBA::NULL_DATETIME;

		$created = $userContactCreated < $publicContactCreated && ($userContactCreated != DBA::NULL_DATETIME) ? $userContactCreated : $publicContactCreated;
		$this->created_at      = DateTimeFormat::utc($created, DateTimeFormat::JSON);

		$this->note            = BBCode::convertForUriId($publicContact['uri-id'] ?? 0, $publicContact['about'], BBCode::EXTERNAL);
		$this->url             = $publicContact['url'];
		$this->avatar          = Contact::getAvatarUrlForId($userContact['id'] ?? 0 ?: $publicContact['id'], Proxy::SIZE_SMALL, $userContact['updated'] ?? '' ?: $publicContact['updated']);
		$this->avatar_static   = $this->avatar;
		$this->header          = Contact::getHeaderUrlForId($userContact['id'] ?? 0 ?: $publicContact['id'], '', $userContact['updated'] ?? '' ?: $publicContact['updated']);
		$this->header_static   = $this->header;
		$this->followers_count = $apcontact['followers_count'] ?? $fcontact['interacted_count'] ?? 0;
		$this->following_count = $apcontact['following_count'] ?? $fcontact['interacting_count'] ?? 0;
		$this->statuses_count  = $apcontact['statuses_count'] ?? $fcontact['post_count'] ?? 0;

		$publicContactLastItem = $publicContact['last-item'] ?: DBA::NULL_DATETIME;
		$userContactLastItem = $userContact['last-item'] ?? DBA::NULL_DATETIME;

		$lastItem = $userContactLastItem > $publicContactLastItem ? $userContactLastItem : $publicContactLastItem;
		$this->last_status_at  = $lastItem != DBA::NULL_DATETIME ? DateTimeFormat::utc($lastItem, 'Y-m-d') : null;

		// No custom emojis per account in Friendica
		$this->emojis          = [];
		$this->fields          = $fields->getArrayCopy();

	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$account = parent::toArray();

		if (empty($account['moved'])) {
			unset($account['moved']);
		}

		return $account;
	}
}
