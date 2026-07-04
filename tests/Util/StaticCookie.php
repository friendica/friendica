<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Model\User\Cookie;

/**
 * Overrides the Cookie class so all cookie information will be saved to a static public variable
 */
class StaticCookie extends Cookie
{
	/** @var array static Cookie array mock */
	public static $_COOKIE = [];
	/** @var int|null The last expire time set */
	public static $_EXPIRE;

	/**
	 * Send a cookie - protected, internal function for test-mocking possibility
	 *
	 * @param string $value  [optional]
	 * @param int    $expire [optional]
	 * @param bool   $secure [optional]
	 * @return bool
	 *
	 * @noinspection PhpMissingParentCallCommonInspection
	 *
	 * @link         https://php.net/manual/en/function.setcookie.php
	 *
	 * @see          Cookie::setCookie()
	 */
	protected function setCookie(?string $value = null, ?int $expire = null, ?bool $secure = null): bool
	{
		self::$_COOKIE[self::NAME] = $value;
		self::$_EXPIRE             = $expire;

		return true;
	}

	public static function clearStatic()
	{
		self::$_EXPIRE = null;
		self::$_COOKIE = [];
	}
}
