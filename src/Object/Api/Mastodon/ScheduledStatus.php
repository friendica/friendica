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

use Friendica\BaseDataTransferObject;
use Friendica\Content\Text\BBCode;
use Friendica\Util\DateTimeFormat;

/**
 * Class ScheduledStatus
 *
 * @see https://docs.joinmastodon.org/entities/scheduledstatus
 */
class ScheduledStatus extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string (Datetime) */
	protected $scheduled_at;
	/** @var array */
	protected $params = [
		'text'           => '',
		'media_ids'      => null,
		'sensitive'      => null,
		'spoiler_text'   => null,
		'visibility'     => '',
		'scheduled_at'   => null,
		'poll'           => null,
		'idempotency'    => null,
		'in_reply_to_id' => null,
		'application_id' => ''
	];
	/** @var Attachment */
	protected $media_attachments = [];

	/**
	 * Creates a status record from a delayed-post record.
	 *
	 * @param array $delayed_post Record with the delayed post
	 * @param array $parameters   Parameters for the workerqueue entry for the delayed post
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $delayed_post, array $parameters, array $media_ids = null, array $media_attachments = [], int $in_reply_to_id = null)
	{
		$visibility = ['public', 'private', 'unlisted'];

		$this->id           = (string)$delayed_post['id'];
		$this->scheduled_at = DateTimeFormat::utc($delayed_post['delayed'], DateTimeFormat::JSON);

		$this->params = [
			'text'           => BBCode::convert(BBCode::setMentionsToNicknames($parameters['item']['body'] ?? ''), false, BBCode::MASTODON_API),
			'media_ids'      => $media_ids,
			'sensitive'      => null,
			'spoiler_text'   => $parameters['item']['title'] ?? '',
			'visibility'     => $visibility[$parameters['item']['private'] ?? 1],
			'scheduled_at'   => $this->scheduled_at,
			'poll'           => null,
			'idempotency'    => null,
			'in_reply_to_id' => $in_reply_to_id,
			'application_id' => ''
		];

		$this->media_attachments = $media_attachments;
	}
}
