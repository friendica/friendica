<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Domain\Entity\Api\Twitter;

use Friendica\Domain\BaseEntity;
use Friendica\Content\ContactSelector;
use Friendica\Content\Text\BBCode;
use Friendica\Domain\Entity\Api\Twitter\Status;

/**
 * @see https://developer.twitter.com/en/docs/tweets/data-dictionary/overview/user-object
 */
class User extends BaseEntity
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
	 * @param array $publicContact         Full contact table record with uid = 0
	 * @param array $apcontact             Optional full apcontact table record
	 * @param array $userContact           Optional full contact table record with uid != 0
	 * @param bool  $skip_status           Whether to remove the last status property, currently unused
	 * @param bool  $include_user_entities Whether to add the entities property
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $publicContact, array $apcontact = [], array $userContact = [], $skip_status = false, $include_user_entities = true)
	{
		$this->id                      = $publicContact['id'];
		$this->id_str                  = (string) $publicContact['id'];
		$this->name                    = $publicContact['name'];
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
		$this->description             = BBCode::toPlaintext($publicContact['about']);
		$this->profile_image_url_https = $userContact['avatar'] ?? $publicContact['avatar'];
		$this->protected               = false;
		$this->followers_count         = $apcontact['followers_count'] ?? 0;
		$this->friends_count           = $apcontact['following_count'] ?? 0;
		$this->listed_count            = 0;
		$this->created_at              = api_date($publicContact['created']);
		$this->favourites_count        = 0;
		$this->verified                = false;
		$this->statuses_count          = $apcontact['statuses_count'] ?? 0;
		$this->profile_banner_url      = '';
		$this->default_profile         = false;
		$this->default_profile_image   = false;

		// @TODO Replace skip_status parameter with an optional Status parameter
		unset($this->status);

		//  Unused optional fields
		unset($this->withheld_in_countries);
		unset($this->withheld_scope);

		// Deprecated
		$this->profile_image_url              = $userContact['avatar'] ?? $publicContact['avatar'];
		$this->profile_image_url_profile_size = $publicContact['thumb'];
		$this->profile_image_url_large        = $publicContact['photo'];
		$this->utc_offset                     = 0;
		$this->time_zone                      = 'UTC';
		$this->geo_enabled                    = false;
		$this->lang                           = null;
		$this->contributors_enabled           = false;
		$this->is_translator                  = false;
		$this->is_translation_enabled         = false;
		$this->following                      = false;
		$this->follow_request_sent            = false;
		$this->statusnet_blocking             = false;
		$this->notifications                  = false;

		// Friendica-specific
		$this->uid                   = $userContact['uid'] ?? 0;
		$this->cid                   = $userContact['id'] ?? 0;
		$this->pid                   = $publicContact['id'];
		$this->self                  = $userContact['self'] ?? false;
		$this->network               = $publicContact['network'];
		$this->statusnet_profile_url = $publicContact['url'];
	}
}
