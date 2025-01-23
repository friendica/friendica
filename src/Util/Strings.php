<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Content\ContactSelector;
use Friendica\DI;
use ParagonIE\ConstantTime\Base64;

/**
 * This class handles string functions
 */
class Strings
{
	/**
	 * Generates a pseudo-random string of hexadecimal characters
	 *
	 * @param int $size Size of string (default: 64)
	 *
	 * @return string Pseudo-random string
	 * @throws \Exception
	 */
	public static function getRandomHex(int $size = 64): string
	{
		$byte_size = ceil($size / 2);

		$bytes = random_bytes($byte_size);

		$return = substr(bin2hex($bytes), 0, $size);

		return $return;
	}

	/**
	 * Checks, if the given string is a valid hexadecimal code
	 *
	 * @param string $hexCode
	 * @return bool
	 */
	public static function isHex(string $hexCode): bool
	{
		return !empty($hexCode) ? @preg_match("/^[a-f0-9]{2,}$/i", $hexCode) && !(strlen($hexCode) & 1) : false;
	}

	/**
	 * Use this on "body" or "content" input where angle chars shouldn't be removed,
	 * and allow them to be safely displayed.
	 * @param string $string
	 *
	 * @return string
	 */
	public static function escapeHtml($string)
	{
		return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
	}

	/**
	 * Generate a string that's random, but usually pronounceable. Used to generate initial passwords
	 *
	 * @param int $len	length
	 * @return string
	 */
	public static function getRandomName(int $len): string
	{
		if ($len <= 0) {
			return '';
		}

		$vowels = ['a', 'a', 'ai', 'au', 'e', 'e', 'e', 'ee', 'ea', 'i', 'ie', 'o', 'ou', 'u'];

		if (mt_rand(0, 5) == 4) {
			$vowels[] = 'y';
		}

		$cons = [
			'b', 'bl', 'br',
			'c', 'ch', 'cl', 'cr',
			'd', 'dr',
			'f', 'fl', 'fr',
			'g', 'gh', 'gl', 'gr',
			'h',
			'j',
			'k', 'kh', 'kl', 'kr',
			'l',
			'm',
			'n',
			'p', 'ph', 'pl', 'pr',
			'qu',
			'r', 'rh',
			's', 'sc', 'sh', 'sm', 'sp', 'st',
			't', 'th', 'tr',
			'v',
			'w', 'wh',
			'x',
			'z', 'zh'
		];

		$midcons = [
			'ck', 'ct', 'gn', 'ld', 'lf', 'lm', 'lt', 'mb', 'mm', 'mn', 'mp',
			'nd', 'ng', 'nk', 'nt', 'rn', 'rp', 'rt'
		];

		$noend = [
			'bl', 'br', 'cl', 'cr', 'dr', 'fl', 'fr', 'gl', 'gr',
			'kh', 'kl', 'kr', 'mn', 'pl', 'pr', 'rh', 'tr', 'qu', 'wh', 'q'
		];

		$start = mt_rand(0, 2);
		if ($start == 0) {
			$table = $vowels;
		} else {
			$table = $cons;
		}

		$word = '';

		for ($x = 0; $x < $len; $x++) {
			$r = mt_rand(0, count($table) - 1);
			$word .= $table[$r];

			if ($table == $vowels) {
				$table = array_merge($cons, $midcons);
			} else {
				$table = $vowels;
			}
		}

		$word = substr($word, 0, $len);

		foreach ($noend as $noe) {
			$noelen = strlen($noe);
			if ((strlen($word) > $noelen) && (substr($word, -$noelen) == $noe)) {
				$word = self::getRandomName($len);
				break;
			}
		}

		return $word;
	}

	/**
	 * Translate and format the network name of a contact
	 *
	 * @param string $network Network name of the contact (e.g. dfrn, rss and so on)
	 * @param string $url	  The contact url
	 *
	 * @return string Formatted network name
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function formatNetworkName(string $network, string $url = ''): string
	{
		if ($network != '') {
			if ($url != '') {
				$gsid         = ContactSelector::getServerIdForProfile($url);
				$network_name = '<a href="' . $url . '">' . ContactSelector::networkToName($network, '', $gsid) . '</a>';
			} else {
				$network_name = ContactSelector::networkToName($network);
			}

			return $network_name;
		}

		return '';
	}

	/**
	 * Remove indentation from a text
	 *
	 * @param string $text	String to be transformed.
	 * @param string $chr	Optional. Indentation tag. Default tab (\t).
	 * @param int	 $count Optional. Default null.
	 *
	 * @return string		Transformed string.
	 */
	public static function deindent(string $text, string $chr = "[\t ]", int $count = null): string
	{
		$lines = explode("\n", $text);

		if (is_null($count)) {
			$m = [];
			$k = 0;
			while ($k < count($lines) && strlen($lines[$k]) == 0) {
				$k++;
			}
			preg_match("|^" . $chr . "*|", $lines[$k], $m);
			$count = strlen($m[0]);
		}

		for ($k = 0; $k < count($lines); $k++) {
			$lines[$k] = preg_replace("|^" . $chr . "{" . $count . "}|", "", $lines[$k]);
		}

		return implode("\n", $lines);
	}

	/**
	 * Get byte size returned in a Data Measurement (KB, MB, GB)
	 *
	 * @param int $bytes	The number of bytes to be measured
	 * @param int $precision	Optional. Default 2.
	 *
	 * @return string	Size with measured units.
	 */
	public static function formatBytes(int $bytes, int $precision = 2): string
	{
		// If this method is called for an infinite (== unlimited) amount of bytes:
		if ($bytes == INF) {
			return INF;
		}

		$units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
		$bytes = max($bytes, 0);
		$pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow   = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * Protect percent characters in sprintf calls
	 *
	 * @param string $s String to transform.
	 * @return string	Transformed string.
	 */
	public static function protectSprintf(string $s): string
	{
		return str_replace('%', '%%', $s);
	}

	/**
	 * Base64 Encode URL and translate +/ to -_ Optionally strip padding.
	 *
	 * @param string $s					URL to encode
	 * @param boolean $strip_padding	Optional. Default false
	 * @return string	Encoded URL
	 * @see https://web.archive.org/web/20160506073138/http://salmon-protocol.googlecode.com:80/svn/trunk/draft-panzer-magicsig-01.html#params
	 */
	public static function base64UrlEncode(string $s, bool $strip_padding = false): string
	{
		if ($strip_padding) {
			$s = Base64::encodeUnpadded($s);
		} else {
			$s = Base64::encode($s);
		}

		return strtr($s, '+/', '-_');
	}

	/**
	 * Decode Base64 Encoded URL and translate -_ to +/
	 *
	 * @param string $s URL to decode
	 * @return string	Decoded URL
	 * @throws \Exception
	 * @see https://web.archive.org/web/20160506073138/http://salmon-protocol.googlecode.com:80/svn/trunk/draft-panzer-magicsig-01.html#params
	 */
	public static function base64UrlDecode(string $s): string
	{
		return Base64::decode(strtr($s, '-_', '+/'));
	}

	/**
	 * Normalize url
	 *
	 * @param string $url	URL to be normalized.
	 * @return string	Normalized URL.
	 */
	public static function normaliseLink(string $url): string
	{
		$ret = str_replace(['https:', '//www.'], ['http:', '//'], $url);
		return rtrim($ret, '/');
	}

	/**
	 * Normalize OpenID identity
	 *
	 * @param string $s OpenID Identity
	 * @return string	normalized OpenId Identity
	 */
	public static function normaliseOpenID(string $s): string
	{
		return trim(str_replace(['http://', 'https://'], ['', ''], $s), '/');
	}

	/**
	 * Compare two URLs to see if they are the same, but ignore
	 * slight but hopefully insignificant differences such as if one
	 * is https and the other isn't, or if one is www.something and
	 * the other isn't - and also ignore case differences.
	 *
	 * @param string $a first url
	 * @param string $b second url
	 * @return boolean True if the URLs match, otherwise False
	 *
	 */
	public static function compareLink(string $a, string $b): bool
	{
		return (strcasecmp(self::normaliseLink($a), self::normaliseLink($b)) === 0);
	}

	/**
	 * Ensures the provided URI has its query string punctuation in order.
	 *
	 * @param string $uri
	 * @return string
	 */
	public static function ensureQueryParameter(string $uri): string
	{
		if (strpos($uri, '?') === false && ($pos = strpos($uri, '&')) !== false) {
			$uri = substr($uri, 0, $pos) . '?' . substr($uri, $pos + 1);
		}

		return $uri;
	}

	/**
	 * Check if the trimmed provided string is starting with one of the provided characters
	 *
	 * @param string $string
	 * @param array $chars
	 *
	 * @return bool
	 */
	public static function startsWithChars(string $string, array $chars): bool
	{
		$return = in_array(substr(trim($string), 0, 1), $chars);

		return $return;
	}

	/**
	 * Check if the first string starts with the second
	 *
	 * @see http://maettig.com/code/php/php-performance-benchmarks.php#startswith
	 * @param string $string
	 * @param string $start
	 * @return bool
	 */
	public static function startsWith(string $string, string $start): bool
	{
		$return = substr_compare($string, $start, 0, strlen($start)) === 0;

		return $return;
	}

	/**
	 * Checks if the first string ends with the second
	 *
	 * @see http://maettig.com/code/php/php-performance-benchmarks.php#endswith
	 * @param string $string
	 * @param string $end
	 *
	 * @return bool
	 */
	public static function endsWith(string $string, string $end): bool
	{
		return (substr_compare($string, $end, -strlen($end)) === 0);
	}

	/**
	 * Returns the regular expression string to match URLs in a given text
	 *
	 * @return string
	 */
	public static function autoLinkRegEx(): string
	{
		return '@
(?<![=\'\]"/]) # Not preceded by [, =, \', ], ", /
\b
(              # Capture 1: entire matched URL
  ' . self::linkRegEx() . '
)@xiu';
	}

	/**
	 * Returns the regular expression string to match only an HTTP URL
	 *
	 * @return string
	 */
	public static function onlyLinkRegEx(): string
	{
		return '@^' . self::linkRegEx() . '$@xiu';
	}

	/**
	 * @return string
	 * @see https://daringfireball.net/2010/07/improved_regex_for_matching_urls
	 */
	private static function linkRegEx(): string
	{
		return 'https?://                   # http or https protocol
  (?:
	[^/\s\xA0`!()\[\]{};:\'",<>?«»“”‘’.]    # Domain can\'t start with a .
	[^/\s\xA0`!()\[\]{};:\'",<>?«»“”‘’]+    # Domain can\'t end with a .
	\.
	[^/\s\xA0`!()\[\]{};:\'".,<>?«»“”‘’]+/? # Followed by a slash
  )
  (?:                                       # One or more:
	[^\s\xA0()<>]+                            # Run of non-space, non-()<>
	|                                         #   or
	\(([^\s\xA0()<>]+|(\([^\s()<>]+\)))*\)    # balanced parens, up to 2 levels
	|								          #   or
	[^\s\xA0`!()\[\]{};:\'".,<>?«»“”‘’]	      # not a space or one of these punct chars
  )*';
	}

	/**
	 * Ensures a single path item doesn't contain any path-traversing characters
	 *
	 * @param string $pathItem
	 *
	 * @see https://stackoverflow.com/a/46097713
	 * @return string
	 */
	public static function sanitizeFilePathItem(string $pathItem): string
	{
		$pathItem = str_replace('/', '_', $pathItem);
		$pathItem = str_replace('\\', '_', $pathItem);
		$pathItem = str_replace(DIRECTORY_SEPARATOR, '_', $pathItem); // In case it does not equal the standard values

		return $pathItem;
	}

	/**
	 * Multi-byte safe implementation of substr_replace where $start and $length are character offset and count rather
	 * than byte offset and counts.
	 *
	 * Depends on mbstring, use default encoding.
	 *
	 * @param string   $string
	 * @param string   $replacement
	 * @param int      $start
	 * @param int|null $length
	 *
	 * @return string
	 * @see substr_replace()
	 */
	public static function substringReplace(string $string, string $replacement, int $start, int $length = null): string
	{
		$string_length = mb_strlen($string);

		$length = $length ?? $string_length;

		if ($start < 0) {
			$start = max(0, $string_length + $start);
		} elseif ($start > $string_length) {
			$start = $string_length;
		}

		if ($length < 0) {
			$length = max(0, $string_length - $start + $length);
		} elseif ($length > $string_length) {
			$length = $string_length;
		}

		if (($start + $length) > $string_length) {
			$length = $string_length - $start;
		}

		return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length);
	}

	/**
	 * Perform a custom function on a text after having escaped blocks matched by the provided regular expressions.
	 * Only full matches are used, capturing group are ignored.
	 *
	 * To change the provided text, the callback function needs to return it and this function will return the modified
	 * version as well after having restored the escaped blocks.
	 *
	 * @param string   $text
	 * @param string   $regex
	 * @param callable $callback
	 *
	 * @return string
	 */
	public static function performWithEscapedBlocks(string $text, string $regex, callable $callback): string
	{
		// Enables nested use
		$executionId = random_int(PHP_INT_MAX / 10, PHP_INT_MAX);

		$blocks = [];

		$return = preg_replace_callback(
			$regex,
			function ($matches) use ($executionId, &$blocks) {
				$return = '«block-' . $executionId . '-' . count($blocks) . '»';

				$blocks[] = $matches[0];

				return $return;
			},
			$text
		);

		if (is_null($return)) {
			DI::logger()->notice('Received null value from preg_replace_callback', ['text' => $text, 'regex' => $regex, 'blocks' => $blocks, 'executionId' => $executionId]);
		}

		$text = $callback($return ?? $text) ?? '';

		// Restore code blocks
		$text = preg_replace_callback(
			'/«block-' . $executionId . '-([0-9]+)»/iU',
			function ($matches) use ($blocks) {
				$return = $matches[0];
				if (isset($blocks[intval($matches[1])])) {
					$return = $blocks[$matches[1]];
				}
				return $return;
			},
			$text
		);

		return $text;
	}

	/**
	 * This function converts a file size string written in PHP's shorthand notation to an integer number of total bytes.
	 * For example: The string for shorthand notation of '2M' (which is 2,097,152 Bytes) is converted to 2097152
	 * @see https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 * @param string $shorthand
	 * @return int
	 */
	public static function getBytesFromShorthand(string $shorthand): int
	{
		$shorthand = trim($shorthand);

		if (ctype_digit($shorthand)) {
			return (int) $shorthand;
		}

		if ($shorthand === '') {
			return 0;
		}

		$last      = strtolower($shorthand[strlen($shorthand) - 1]);
		$shorthand = substr($shorthand, 0, -1);

		switch ($last) {
			case 'g':
				$shorthand *= 1024;
				// no break
			case 'm':
				$shorthand *= 1024;
				// no break
			case 'k':
				$shorthand *= 1024;
		}

		return $shorthand;
	}

	/**
	 * Converts an URL in a nicer format (without the scheme and possibly shortened)
	 *
	 * @param string $url        URL that is about to be reformatted
	 * @param int    $max_length Maximum length of an url before it is shortened
	 * @return string reformatted link
	 */
	public static function getStyledURL(string $url, int $max_length = 30): string
	{
		$parts = parse_url($url);
		if (empty($parts['scheme'])) {
			return $url;
		}

		$scheme     = [$parts['scheme'] . '://www.', $parts['scheme'] . '://'];
		$styled_url = str_replace($scheme, '', $url);

		if (!empty($max_length) && strlen($styled_url) > $max_length) {
			$styled_url = substr($styled_url, 0, $max_length) . "…";
		}

		return $styled_url;
	}

	/**
	 * Sort a comma separated list of hashtags, convert them to lowercase and remove duplicates
	 *
	 * @param string $tag_list
	 * @return string
	 */
	public static function cleanTags(string $tag_list): string
	{
		$tags = [];

		$tagitems = explode(',', str_replace([' ', ';', '#'], ',', mb_strtolower($tag_list)));
		foreach ($tagitems as $tag) {
			if (!empty($tag)) {
				$tags[] = preg_replace('#\s#u', '', $tag);
			}
		}
		$tags = array_unique($tags);
		asort($tags);
		return implode(',', $tags);
	}

	/**
	 * Get a tag array out of a comma separated list of tags
	 *
	 * @param string $tag_list
	 * @return array
	 */
	public static function getTagArrayByString(string $tag_list): array
	{
		return explode(',', self::cleanTags($tag_list));
	}
}
