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
use Friendica\Util\DateTimeFormat;

/**
 * Class DirectMessage
 */
class DirectMessage extends BaseDataTransferObject
{
	/** @var int */
	protected $id;
	/** @var int */
	protected $sender_id;
	/** @var string */
	protected $text;
	/** @var int */
	protected $recipient_id;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var string */
	protected $sender_screen_name = null;
	/** @var string */
	protected $recipient_screen_name = null;
	/** @var User */
	protected $sender;
	/** @var User */
	protected $recipient;
	/** @var string|null */
	protected $title;
	/** @var bool */
	protected $friendica_seen;
	/** @var string|null */
	protected $friendica_parent_uri = null;

	/**
	 * Creates a direct message record
	 *
	 * @param array  $mail
	 * @param User   $sender
	 * @param User   $recipient
	 * @param string $text
	 * @param string $title
	 */
	public function __construct(array $mail, User $sender, User $recipient, string $text, string $title = null)
	{
		$this->id                    = (int)$mail['id'];
		$this->created_at            = DateTimeFormat::utc($mail['created'] ?? 'now', DateTimeFormat::API);
		$this->title                 = $title;
		$this->text                  = $text;
		$this->sender                = $sender->toArray();
		$this->recipient             = $recipient->toArray();
		$this->sender_id             = (int)$this->sender['id'];
		$this->recipient_id          = (int)$this->recipient['id'];
		$this->sender_screen_name    = $this->sender['screen_name'];
		$this->recipient_screen_name = $this->recipient['screen_name'];
		$this->friendica_seen        = (bool)$mail['seen'] ?? false;
		$this->friendica_parent_uri  = $mail['parent-uri'] ?? '';
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (is_null($status['title'])) {
			unset($status['title']);
		}

		unset($status['sender']['uid']);
		unset($status['recipient']['uid']);

		return $status;
	}
}
