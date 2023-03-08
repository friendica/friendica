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
use Friendica\Model\Item;
use Friendica\Util\DateTimeFormat;

/**
 * Class Status
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/tweet
 */
class Status extends BaseDataTransferObject
{
	/** @var string */
	protected $text;
	/** @var bool */
	protected $truncated;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var int|null */
	protected $in_reply_to_status_id = null;
	/** @var string|null */
	protected $in_reply_to_status_id_str = null;
	/** @var string */
	protected $source;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var int|null */
	protected $in_reply_to_user_id = null;
	/** @var string|null */
	protected $in_reply_to_user_id_str = null;
	/** @var string|null */
	protected $in_reply_to_screen_name = null;
	/** @var array|null */
	protected $geo;
	/** @var bool */
	protected $favorited = false;
	/** @var User */
	protected $user;
	/** @var User */
	protected $friendica_author;
	/** @var User */
	protected $friendica_owner;
	/** @var bool */
	protected $friendica_private;
	/** @var string */
	protected $statusnet_html;
	/** @var int */
	protected $statusnet_conversation_id;
	/** @var string */
	protected $external_url;
	/** @var array */
	protected $friendica_activities;
	/** @var string */
	protected $friendica_title;
	/** @var string */
	protected $friendica_html;
	/** @var int */
	protected $friendica_comments;
	/** @var Status|null */
	protected $retweeted_status = null;
	/** @var Status|null */
	protected $quoted_status = null;
	/** @var array */
	protected $attachments;
	/** @var array */
	protected $entities;
	/** @var array */
	protected $extended_entities;

	/**
	 * Creates a status record from an item record.
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $text, string $statusnetHtml, string $friendicaHtml, array $item, User $author, User $owner, array $retweeted, array $quoted, array $geo, array $friendica_activities, array $entities, array $attachments, int $friendica_comments, bool $liked)
	{
		$this->id                        = (int)$item['uri-id'];
		$this->id_str                    = (string)$item['uri-id'];
		$this->statusnet_conversation_id = (int)$item['parent-uri-id'];

		$this->created_at = DateTimeFormat::utc($item['created'], DateTimeFormat::API);

		if ($item['gravity'] == Item::GRAVITY_COMMENT) {
			$this->in_reply_to_status_id     = (int)$item['thr-parent-id'];
			$this->in_reply_to_status_id_str = (string)$item['thr-parent-id'];
			$this->in_reply_to_user_id       = (int)$item['parent-author-id'];
			$this->in_reply_to_user_id_str   = (string)$item['parent-author-id'];
			$this->in_reply_to_screen_name   = $item['parent-author-nick'];
		}

		$this->text                 = $text;
		$this->friendica_title      = $item['title'];
		$this->statusnet_html       = $statusnetHtml;
		$this->friendica_html       = $friendicaHtml;
		$this->user                 = $owner->toArray();
		$this->friendica_author     = $author->toArray();
		$this->friendica_owner      = $owner->toArray();
		$this->truncated            = false;
		$this->friendica_private    = $item['private'] == Item::PRIVATE;
		$this->retweeted_status     = $retweeted;
		$this->quoted_status        = $quoted;
		$this->external_url         = $item['plink'];
		$this->favorited            = $liked;
		$this->friendica_comments   = $friendica_comments;
		$this->source               = $item['app'];
		$this->geo                  = $geo;
		$this->friendica_activities = $friendica_activities;
		$this->attachments          = $attachments;
		$this->entities             = $entities;
		$this->extended_entities    = $entities;

		$origin = ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']);

		if (empty($this->source)) {
			$this->source = $origin;
		} elseif ($origin != $this->source) {
			$this->source = trim($this->source. ' (' . $origin . ')');
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['retweeted_status'])) {
			unset($status['retweeted_status']);
		}

		if (empty($status['quoted_status'])) {
			unset($status['quoted_status']);
		}

		if (empty($status['geo'])) {
			$status['geo'] = null;
		}

		if (empty($status['entities'])) {
			$status['entities'] = null;
		}

		if (empty($status['extended_entities'])) {
			$status['extended_entities'] = null;
		}

		if (empty($status['attachments'])) {
			$status['attachments'] = null;
		}


		return $status;
	}
}
