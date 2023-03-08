<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\Util;

use Friendica\Model\User\Cookie;

/**
 * Overrides the Cookie class so all cookie information will be saved to a static public variable
 */
class StaticCookie extends Cookie
{
	/** @var array static Cookie array mock */
	public static $_COOKIE = [];
	/** @var int The last expire time set */
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
	protected function setCookie(string $value = null, int $expire = null, bool $secure = null): bool
	{
		self::$_COOKIE[self::NAME] = $value;
		self::$_EXPIRE = $expire;

		return true;
	}

	public static function clearStatic()
	{
		self::$_EXPIRE = null;
		self::$_COOKIE = [];
	}
}
