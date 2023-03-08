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

use Exception;
use Friendica\BaseDataTransferObject;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;

/**
 * Class Notification
 *
 * @see https://docs.joinmastodon.org/entities/notification/
 */
class Notification extends BaseDataTransferObject
{
	/* From the Mastodon documentation:
	 * - follow         = Someone followed you
	 * - follow_request = Someone requested to follow you
	 * - mention        = Someone mentioned you in their status
	 * - reblog         = Someone boosted one of your statuses
	 * - favourite      = Someone favourited one of your statuses
	 * - poll           = A poll you have voted in or created has ended
	 * - status         = Someone you enabled notifications for has posted a status
	 */
	public const TYPE_FOLLOW       = 'follow';
	public const TYPE_INTRODUCTION = 'follow_request';
	public const TYPE_MENTION      = 'mention';
	public const TYPE_RESHARE      = 'reblog';
	public const TYPE_LIKE         = 'favourite';
	public const TYPE_POLL         = 'poll';
	public const TYPE_POST         = 'status';

	/** @var string */
	protected $id;
	/** @var string One of the TYPE_* constant values */
	protected $type;
	/** @var string (Datetime) */
	protected $created_at;
	/** @var bool */
	protected $dismissed;
	/** @var Account */
	protected $account;
	/** @var Status|null */
	protected $status = null;

	/**
	 * Creates a notification record
	 *
	 * @throws HttpException\InternalServerErrorException|Exception
	 */
	public function __construct(int $id, string $type, \DateTime $created_at, Account $account = null, Status $status = null, bool $dismissed = false)
	{
		$this->id         = (string)$id;
		$this->type       = $type;
		$this->created_at = $created_at->format(DateTimeFormat::JSON);
		$this->account    = $account->toArray();
		$this->dismissed  = $dismissed;

		if (!empty($status)) {
			$this->status = $status->toArray();
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$notification = parent::toArray();

		if (!$notification['status']) {
			unset($notification['status']);
		}

		return $notification;
	}
}
