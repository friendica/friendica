<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\Status;

/**
 * Class UserAttributes
 *
 * @property-read bool $favourited
 * @property-read bool $reblogged
 * @property-read bool $muted
 * @property-read bool $bookmarked
 * @property-read bool $pinned
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class UserAttributes
{
	/** @var bool */
	protected $favourited;
	/** @var bool */
	protected $reblogged;
	/** @var bool */
	protected $muted;
	/** @var bool */
	protected $bookmarked;
	/** @var bool */
	protected $pinned;

	/**
	 * Creates a authorized user attributes object
	 *
	 * @param bool $favourited
	 * @param bool $reblogged
	 * @param bool $muted
	 * @param bool $bookmarked
	 * @param bool $pinned
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(bool $favourited, bool $reblogged, bool $muted, bool $bookmarked, bool $pinned)
	{
		$this->favourited = $favourited;
		$this->reblogged  = $reblogged;
		$this->muted      = $muted;
		$this->bookmarked = $bookmarked;
		$this->pinned     = $pinned;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
