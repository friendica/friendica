<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	/** @var array */
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
			'text'           => BBCode::convertForUriId($parameters['item']['uri-id'] ?? 0, BBCode::setMentionsToNicknames($parameters['item']['body'] ?? ''), BBCode::MASTODON_API),
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
