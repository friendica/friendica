<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\TwoFactor\Model;

use Friendica\BaseEntity;
use Friendica\Util\DateTimeFormat;

/**
 * Class TrustedBrowser
 *
 *
 * @property-read string $cookie_hash
 * @property-read int $uid
 * @property-read string $user_agent
 * @property-read bool $trusted
 * @property-read string $created
 * @property-read string|null $last_used
 * @package Friendica\Model\TwoFactor
 */
class TrustedBrowser extends BaseEntity
{
	protected $cookie_hash;
	protected $uid;
	protected $user_agent;
	protected $trusted;
	protected $created;
	protected $last_used;

	/**
	 * Please do not use this constructor directly, instead use one of the method of the TrustedBrowser factory.
	 *
	 * @see \Friendica\Security\TwoFactor\Factory\TrustedBrowser
	 *
	 * @param string      $cookie_hash
	 * @param int         $uid
	 * @param string      $user_agent
	 * @param bool        $trusted
	 * @param string      $created
	 * @param string|null $last_used
	 */
	public function __construct(
		string $cookie_hash,
		int $uid,
		string $user_agent,
		bool $trusted,
		string $created,
		?string $last_used = null,
	) {
		$this->cookie_hash = $cookie_hash;
		$this->uid         = $uid;
		$this->user_agent  = $user_agent;
		$this->trusted     = $trusted;
		$this->created     = $created;
		$this->last_used   = $last_used;
	}

	/**
	 * Records if the trusted browser was used
	 *
	 * @return void
	 * @throws \Exception unexpected DateTime exception happened
	 */
	public function recordUse()
	{
		$this->last_used = DateTimeFormat::utcNow();
	}
}
