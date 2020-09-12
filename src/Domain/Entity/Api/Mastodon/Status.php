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

namespace Friendica\Domain\Entity\Api\Mastodon;

use Friendica\Domain\BaseEntity;
use Friendica\Content\Text\BBCode;
use Friendica\Util\DateTimeFormat;

/**
 * Class Status
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class Status extends BaseEntity
{
	/** @var string */
	protected $id;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var string|null */
	protected $in_reply_to_id = null;
	/** @var string|null */
	protected $in_reply_to_account_id = null;
	/** @var bool */
	protected $sensitive = false;
	/** @var string */
	protected $spoiler_text = "";
	/** @var string (Enum of public, unlisted, private, direct)*/
	protected $visibility;
	/** @var string|null */
	protected $language = null;
	/** @var string */
	protected $uri;
	/** @var string|null (URL)*/
	protected $url = null;
	/** @var int */
	protected $replies_count = 0;
	/** @var int */
	protected $reblogs_count = 0;
	/** @var int */
	protected $favourites_count = 0;
	/** @var bool */
	protected $favourited = false;
	/** @var bool */
	protected $reblogged = false;
	/** @var bool */
	protected $muted = false;
	/** @var bool */
	protected $bookmarked = false;
	/** @var bool */
	protected $pinned = false;
	/** @var string */
	protected $content;
	/** @var Status|null */
	protected $reblog = null;
	/** @var Application */
	protected $application = null;
	/** @var Account */
	protected $account;
	/** @var Attachment */
	protected $media_attachments = [];
	/** @var Mention */
	protected $mentions = [];
	/** @var Tag */
	protected $tags = [];
	/** @var Emoji[] */
	protected $emojis = [];
	/** @var Card|null */
	protected $card = null;
	/** @var Poll|null */
	protected $poll = null;

	/**
	 * Creates a status record from an item record.
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $item, Account $account)
	{
		$this->id         = (string)$item['uri-id'];
		$this->created_at = DateTimeFormat::utc($item['created'], DateTimeFormat::ATOM);

		if ($item['gravity'] == GRAVITY_COMMENT) {
			$this->in_reply_to_id         = (string)$item['thr-parent-id'];
			$this->in_reply_to_account_id = (string)$item['parent-author-id'];
		}

		$this->sensitive = false;
		$this->spoiler_text = $item['title'];

		$visibility = ['public', 'private', 'unlisted'];
		$this->visibility = $visibility[$item['private']];

		$this->language = null;
		$this->uri = $item['uri'];
		$this->url = $item['plink'] ?? null;
		$this->replies_count = 0;
		$this->reblogs_count = 0;
		$this->favourites_count = 0;
		$this->favourited = false;
		$this->reblogged = false;
		$this->muted = false;
		$this->bookmarked = false;
		$this->pinned = false;
		$this->content = BBCode::convert($item['body'], false);
		$this->reblog = null;
		$this->application = null;
		$this->account = $account->toArray();
		$this->media_attachments = [];
		$this->mentions = [];
		$this->tags = [];
		$this->emojis = [];
		$this->card = null;
		$this->poll = null;
	}
}
