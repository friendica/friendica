<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\Status;

/**
 * Class Counts
 *
 * @see https://docs.joinmastodon.org/entities/status
 *
 * @property-read int $replies
 * @property-read int $reblogs
 * @property-read int $favourites
 * @property-read int $dislikes
 * @property-read int $quotes
 */
class Counts
{
	/** @var int */
	protected $replies;
	/** @var int */
	protected $reblogs;
	/** @var int */
	protected $favourites;

	/** @var int */
	protected $dislikes;

	/** @var int */
	protected $quotes;

	/**
	 * Creates a status count object
	 *
	 * @param int $replies
	 * @param int $reblogs
	 * @param int $favourites
	 * @param int $dislikes
	 * @param int $quotes
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $replies, int $reblogs, int $favourites, int $dislikes, int $quotes = 0)
	{
		$this->replies    = $replies;
		$this->reblogs    = $reblogs;
		$this->favourites = $favourites;
		$this->dislikes   = $dislikes;
		$this->quotes     = $quotes;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
