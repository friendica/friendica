<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Conversation
 *
 * @see https://docs.joinmastodon.org/entities/conversation/
 */
class Conversation extends BaseDataTransferObject
{
	//Required attributes
	/** @var string */
	protected $id;
	/** @var array */
	protected $accounts;
	/** @var bool */
	protected $unread;

	// Optional attributes
	/**
	 * @var Status
	 */
	protected $last_status = null;

	public function __construct(
		string $id,
		array $accounts,
		bool $unread,
		?Status $last_status = null,
	) {
		$this->id          = (string) $id;
		$this->accounts    = $accounts;
		$this->unread      = $unread;
		$this->last_status = $last_status;
	}
}
