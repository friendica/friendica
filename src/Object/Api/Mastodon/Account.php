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
	 * @param array   $account entry of "account-user-view"
	 * @param Fields  $fields  Profile fields
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $account, Fields $fields)
	{
		$this->id              = (string)$account['pid'];
		$this->username        = $account['nick'];
		$this->acct            =
			strpos($account['url'], $baseUrl . '/') === 0 ?
				$account['nick'] :
				$account['addr'];
		$this->display_name    = $account['name'];
		$this->locked          = (bool)$account['manually-approve'];
		$this->bot             = ($account['contact-type'] == Contact::TYPE_NEWS);
		$this->discoverable    = !$account['unsearchable'];
		$this->group           = ($account['contact-type'] == Contact::TYPE_COMMUNITY);

		$this->created_at      = DateTimeFormat::utc($account['created'] ?: DBA::NULL_DATETIME, DateTimeFormat::JSON);

		$this->note            = BBCode::convertForUriId($account['uri-id'], $account['about'], BBCode::EXTERNAL);
		$this->url             = $account['url'];
		$this->avatar          = Contact::getAvatarUrlForId($account['id'] ?? 0 ?: $account['pid'], Proxy::SIZE_SMALL, $account['updated'], $account['guid'] ?? '');
		$this->avatar_static   = Contact::getAvatarUrlForId($account['id'] ?? 0 ?: $account['pid'], Proxy::SIZE_SMALL, $account['updated'], $account['guid'] ?? '', true);
		$this->header          = Contact::getHeaderUrlForId($account['id'] ?? 0 ?: $account['pid'], '', $account['updated'], $account['guid'] ?? '');
		$this->header_static   = Contact::getHeaderUrlForId($account['id'] ?? 0 ?: $account['pid'], '', $account['updated'], $account['guid'] ?? '', true);
		$this->followers_count = $account['ap-followers_count'] ?? $account['diaspora-interacted_count'] ?? 0;
		$this->following_count = $account['ap-following_count'] ?? $account['diaspora-interacting_count'] ?? 0;
		$this->statuses_count  = $account['ap-statuses_count'] ?? $account['diaspora-post_count'] ?? 0;

		$lastItem = $account['last-item'] ? DateTimeFormat::utc($account['last-item'], 'Y-m-d') : DBA::NULL_DATETIME;
		$this->last_status_at  = $lastItem != DBA::NULL_DATETIME ? DateTimeFormat::utc($lastItem, DateTimeFormat::JSON) : null;

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
