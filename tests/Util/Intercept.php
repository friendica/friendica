<?php

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
