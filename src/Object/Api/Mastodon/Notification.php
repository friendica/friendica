<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	 * - admin.sign_up  = Someone signed up (optionally sent to admins)
	 * - admin.report   = A new report has been filed
	 * - follow         = Someone followed you
	 * - follow_request = Someone requested to follow you
	 * - mention        = Someone mentioned you in their status
	 * - reblog         = Someone boosted one of your statuses
	 * - favourite      = Someone favourited one of your statuses
	 * - poll           = A poll you have voted in or created has ended
	 * - quote          = Someone quoted one of your posts
	 * - quoted_update  = A status you have quoted has been edited
	 * - status         = Someone you enabled notifications for has posted a status
	 * - update         = A status you boosted with has been edited
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
	/** @var array */
	protected $account;
	/** @var array|null */
	protected $status = null;

	/**
	 * Creates a notification record
	 *
	 * @throws HttpException\InternalServerErrorException|Exception
	 */
	public function __construct(int $id, string $type, \DateTime $created_at, Account $account = null, Status $status = null, bool $dismissed = false)
	{
		$this->id         = (string) $id;
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
