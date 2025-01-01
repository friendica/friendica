<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Activity
 *
 * @see https://docs.joinmastodon.org/entities/activity
 */
class Activity extends BaseDataTransferObject
{
	/** @var string (UNIX Timestamp) */
	protected $week;
	/** @var string */
	protected $statuses;
	/** @var string */
	protected $logins;
	/** @var string */
	protected $registrations;

	/**
	 * Creates an activity
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $week, int $statuses, int $logins, int $registrations)
	{
		$this->week          = (string)$week;
		$this->statuses      = (string)$statuses;
		$this->logins        = (string)$logins;
		$this->registrations = (string)$registrations;
	}
}
