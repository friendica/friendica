<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class Poll
 *
 * @see https://docs.joinmastodon.org/entities/poll/
 */
class Poll extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string|null (Datetime) */
	protected $expires_at;
	/** @var bool */
	protected $expired = false;
	/** @var bool */
	protected $multiple = false;
	/** @var int */
	protected $votes_count = 0;
	/** @var int|null */
	protected $voters_count = 0;
	/** @var bool|null */
	protected $voted = false;
	/** @var array|null */
	protected $own_votes = null;
	/** @var array */
	protected $options = [];
	/** @var Emoji[] */
	protected $emojis = [];

	/**
	 * Creates a poll record.
	 *
	 * @param array $question Array with the question
	 * @param array $options  Array of question options
	 * @param bool  $expired  "true" if the question is expired
	 * @param int   $votes    Number of total votes
	 * @param array $ownvotes Own vote
	 */
	public function __construct(array $question, array $options, bool $expired, int $votes, array $ownvotes = null, bool $voted = null)
	{
		$this->id           = (string)$question['id'];
		$this->expires_at   = !empty($question['end-time']) ? DateTimeFormat::utc($question['end-time'], DateTimeFormat::JSON) : null;
		$this->expired      = $expired;
		$this->multiple     = (bool)$question['multiple'];
		$this->votes_count  = $votes;
		$this->voters_count = $this->multiple ? $question['voters'] : null;
		$this->voted        = $voted;
		$this->own_votes    = $ownvotes;
		$this->options      = $options;
		$this->emojis       = [];
	}

	public function toArray(): array
	{
		$status = parent::toArray();

		if (is_null($status['voted'])) {
			unset($status['voted']);
		}

		if (is_null($status['own_votes'])) {
			unset($status['own_votes']);
		}
		return $status;
	}
}
