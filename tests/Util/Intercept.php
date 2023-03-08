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

use php_user_filter;

/**
 * Output Interceptor for STDOUT to prevent outputing to the console
 * Instead the $cache variable will get filled with the output
 *
 * @package Friendica\Test\Util
 */
class Intercept extends php_user_filter
{
	/**
	 * @var string The cache which holds the current output of STDOUT
	 */
	public static $cache = '';

	/** @noinspection PhpMissingParentCallCommonInspection */
	public function filter($in, $out, &$consumed, $closing)
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			self::$cache .= $bucket->data;
			$consumed += $bucket->datalen;
			stream_bucket_append($out, $bucket);
		}
		return PSFS_FEED_ME;
	}

	/**
	 * Registers the interceptor and prevents therefore the output to STDOUT
	 */
	public static function setUp() {
		stream_filter_register("intercept", Intercept::class);
		stream_filter_append(STDOUT, "intercept");
		stream_filter_append(STDERR, "intercept");
	}

	/**
	 * Resets the cache
	 */
	public static function reset() {
		self::$cache = '';
	}
}
