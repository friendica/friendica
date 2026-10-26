<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Polls
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Polls extends BaseDataTransferObject
{
	/** @var int */
	protected $max_options = 0;
	/** @var int */
	protected $max_characters_per_option = 0;
	/** @var int */
	protected $min_expiration = 0;
	/** @var int */
	protected $max_expiration = 0;

	/**
	 * @param int $max_options
	 * @param int $max_characters_per_option
	 * @param int $min_expiration
	 * @param int $max_expiration
	 */
	public function __construct(int $max_options, int $max_characters_per_option, int $min_expiration, int $max_expiration)
	{
		$this->max_options               = $max_options;
		$this->max_characters_per_option = $max_characters_per_option;
		$this->min_expiration            = $min_expiration;
		$this->max_expiration            = $max_expiration;
	}
}
