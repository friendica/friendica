<?php

namespace Friendica\Api\Mastodon;

use Friendica\Api\Entity;
use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

/**
 * Class Account
 *
 * @see https://docs.joinmastodon.org/entities/account
 */
class Account extends Entity
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
	/** @var string (Datetime) */
	protected $created_at;
	/** @var int */
	protected $followers_count;
	/** @var int */
	protected $following_count;
	/** @var int */
	protected $statuses_count;
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
	/** @var Emoji[] */
	protected $emojis;
	/** @var Account|null */
	protected $moved = null;
	/** @var Field[]|null */
	protected $fields = null;
	/** @var bool|null */
	protected $bot = null;
	/** @var bool */
	protected $group;
	/** @var bool */
	protected $discoverable;
	/** @var string|null (Datetime) */
	protected $last_status_at = null;

	/**
	 * Creates an account record from a public contact record. Expects all contact table fields to be set.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $publicContact Full contact table record with uid = 0
	 * @param array   $apcontact     Optional full apcontact table record
	 * @param array   $userContact   Optional full contact table record with uid != 0
	 * @return Account
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function create(BaseURL $baseUrl, array $publicContact, array $apcontact = [], array $userContact = [])
	{
		$account = new Account();
		$account->id              = $publicContact['id'];
		$account->username        = $publicContact['nick'];
		$account->acct            =
			strpos($publicContact['url'], $baseUrl->get() . '/') === 0 ?
			$publicContact['nick'] :
			$publicContact['addr'];
		$account->display_name    = $publicContact['name'];
		$account->locked          = !empty($apcontact['manually-approve']);
		$account->created_at      = DateTimeFormat::utc($publicContact['created'], DateTimeFormat::ATOM);
		$account->followers_count = $apcontact['followers_count'] ?? 0;
		$account->following_count = $apcontact['following_count'] ?? 0;
		$account->statuses_count  = $apcontact['statuses_count'] ?? 0;
		$account->note            = BBCode::convert($publicContact['about'], false);
		$account->url             = $publicContact['url'];
		$account->avatar          = $userContact['avatar'] ?? $publicContact['avatar'];
		$account->avatar_static   = $userContact['avatar'] ?? $publicContact['avatar'];
		// No header picture in Friendica
		$account->header          = '';
		$account->header_static   = '';
		// No custom emojis per account in Friendica
		$account->emojis          = [];
		// No metadata fields in Friendica
		$account->fields          = [];
		$account->bot             = ($publicContact['contact-type'] == Contact::TYPE_NEWS);
		$account->group           = ($publicContact['contact-type'] == Contact::TYPE_COMMUNITY);
		$account->discoverable    = !$publicContact['unsearchable'];

		$publicContactLastItem = $publicContact['last-item'] ?: DBA::NULL_DATETIME;
		$userContactLastItem = $userContact['last-item'] ?? DBA::NULL_DATETIME;

		$lastItem = $userContactLastItem > $publicContactLastItem ? $userContactLastItem : $publicContactLastItem;
		$account->last_status_at  = $lastItem != DBA::NULL_DATETIME ? DateTimeFormat::utc($lastItem, DateTimeFormat::ATOM) : null;

		return $account;
	}
}
