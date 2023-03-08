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

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;
use Friendica\Content\ContactSelector;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;

/**
 * @see https://developer.twitter.com/en/docs/tweets/data-dictionary/overview/user-object
 */
class User extends BaseDataTransferObject
{
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var string */
	protected $name;
	/** @var string */
	protected $screen_name;
	/** @var string|null */
	protected $location;
	/** @var array */
	protected $derived;
	/** @var string|null */
	protected $url;
	/** @var array */
	protected $entities;
	/** @var string|null */
	protected $description;
	/** @var bool */
	protected $protected;
	/** @var bool */
	protected $verified;
	/** @var int */
	protected $followers_count;
	/** @var int */
	protected $friends_count;
	/** @var int */
	protected $listed_count;
	/** @var int */
	protected $favourites_count;
	/** @var int */
	protected $statuses_count;
	/** @var string */
	protected $created_at;
	/** @var string */
	protected $profile_banner_url;
	/** @var string */
	protected $profile_image_url_https;
	/** @var bool */
	protected $default_profile;
	/** @var bool */
	protected $default_profile_image;
	/** @var Status */
	protected $status;
	/** @var array */
	protected $withheld_in_countries;
	/** @var string */
	protected $withheld_scope;

	/**
	 * Missing fields:
	 *
	 * - profile_sidebar_fill_color
	 * - profile_link_color
	 * - profile_background_color
	 */

	/**
	 * @param array $publicContact         Full contact table record with uid = 0
	 * @param array $apcontact             Optional full apcontact table record
	 * @param array $userContact           Optional full contact table record with uid != 0
	 * @param bool  $skip_status           Whether to remove the last status property, currently unused
	 * @param bool  $include_user_entities Whether to add the entities property
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $publicContact, array $apcontact = [], array $userContact = [], $status = null, $include_user_entities = true)
	{
		$uid = $userContact['uid'] ?? 0;

		$this->id                      = (int)$publicContact['id'];
		$this->id_str                  = (string) $publicContact['id'];
		$this->name                    = $publicContact['name'] ?: $publicContact['nick'];
		$this->screen_name             = $publicContact['nick'] ?: $publicContact['name'];
		$this->location                = $publicContact['location'] ?:
			ContactSelector::networkToName($publicContact['network'], $publicContact['url'], $publicContact['protocol']);
		$this->derived                 = [];
		$this->url                     = $publicContact['url'];
		// No entities needed since we don't perform any shortening in the URL or description
		$this->entities            = [
			'url' => ['urls' => []],
			'description' => ['urls' => []],
		];
		if (!$include_user_entities) {
			unset($this->entities);
		}
		$this->description             = (!empty($publicContact['about']) ? BBCode::toPlaintext($publicContact['about']) : '');
		$this->profile_image_url_https = Contact::getAvatarUrlForUrl($publicContact['url'], $uid, Proxy::SIZE_MICRO);
		$this->protected               = false;
		$this->followers_count         = $apcontact['followers_count'] ?? 0;
		$this->friends_count           = $apcontact['following_count'] ?? 0;
		$this->listed_count            = 0;
		$this->created_at              = DateTimeFormat::utc($publicContact['created'], DateTimeFormat::API);
		$this->favourites_count        = 0;
		$this->verified                = $uid != 0;
		$this->statuses_count          = $apcontact['statuses_count'] ?? 0;
		$this->profile_banner_url      = Contact::getHeaderUrlForId($publicContact['id'], '', $publicContact['updated']);
		$this->default_profile         = false;
		$this->default_profile_image   = false;

		if (!empty($status)) {
			$this->status = $status;
		} else {
			unset($this->status);
		}

		//  Unused optional fields
		unset($this->withheld_in_countries);
		unset($this->withheld_scope);

		// Deprecated
		$this->profile_image_url              = Contact::getAvatarUrlForUrl($publicContact['url'], $uid, Proxy::SIZE_MICRO);
		$this->profile_image_url_profile_size = Contact::getAvatarUrlForUrl($publicContact['url'], $uid, Proxy::SIZE_THUMB);
		$this->profile_image_url_large        = Contact::getAvatarUrlForUrl($publicContact['url'], $uid, Proxy::SIZE_LARGE);
		$this->utc_offset                     = 0;
		$this->time_zone                      = 'UTC';
		$this->geo_enabled                    = false;
		$this->lang                           = null;
		$this->contributors_enabled           = false;
		$this->is_translator                  = false;
		$this->is_translation_enabled         = false;
		$this->following                      = in_array($userContact['rel'] ?? Contact::NOTHING, [Contact::FOLLOWER, Contact::FRIEND]);
		$this->follow_request_sent            = false;
		$this->statusnet_blocking             = false;
		$this->notifications                  = false;

		// Friendica-specific
		$this->uid                   = (int)$uid;
		$this->cid                   = (int)($userContact['id'] ?? 0);
		$this->pid                   = (int)$publicContact['id'];
		$this->statusnet_profile_url = $publicContact['url'];
	}
}
