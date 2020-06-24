<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Util;

use Friendica\Content\ContactSelector;
use Friendica\Core\Logger;

/**
 * This class handles string functions
 */
class Strings
{
	/**
	 * Generates a pseudo-random string of hexadecimal characters
	 *
	 * @param int $size
	 * @return string
	 * @throws \Exception
	 */
	public static function getRandomHex($size = 64)
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
	 *
	 * @return bool
	 */
	public static function isHex($hexCode)
	{
		return !empty($hexCode) ? @preg_match("/^[a-f0-9]{2,}$/i", $hexCode) && !(strlen($hexCode) & 1) : false;
	}

	/**
	 * This is our primary input filter.
	 *
	 * Use this on any text input where angle chars are not valid or permitted
	 * They will be replaced with safer brackets. This may be filtered further
	 * if these are not allowed either.
	 *
	 * @param string $string Input string
	 * @return string Filtered string
	 */
	public static function escapeTags($string)
	{
		return str_replace(["<", ">"], ['[', ']'], $string);
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
	 *
	 * @return string
	 */
	public static function getRandomName($len)
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
	public static function formatNetworkName($network, $url = '')
	{
		if ($network != '') {
			if ($url != '') {
				$network_name = '<a href="' . $url . '">' . ContactSelector::networkToName($network, $url) . '</a>';
			} else {
				$network_name = ContactSelector::networkToName($network);
			}

			return $network_name;
		}
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
	public static function deindent($text, $chr = "[\t ]", $count = NULL)
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
	public static function formatBytes($bytes, $precision = 2)
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * Protect percent characters in sprintf calls
	 *
	 * @param string $s String to transform.
	 *
	 * @return string	Transformed string.
	 */
	public static function protectSprintf($s)
	{
		return str_replace('%', '%%', $s);
	}

	/**
	 * Base64 Encode URL and translate +/ to -_ Optionally strip padding.
	 *
	 * @param string $s					URL to encode
	 * @param boolean $strip_padding	Optional. Default false
	 *
	 * @return string	Encoded URL
	 */
	public static function base64UrlEncode($s, $strip_padding = false)
	{
		$s = strtr(base64_encode($s), '+/', '-_');

		if ($strip_padding) {
			$s = str_replace('=', '', $s);
		}

		return $s;
	}

	/**
	 * Decode Base64 Encoded URL and translate -_ to +/
	 * @param string $s URL to decode
	 *
	 * @return string	Decoded URL
	 * @throws \Exception
	 */
	public static function base64UrlDecode($s)
	{
		if (is_array($s)) {
			Logger::log('base64url_decode: illegal input: ' . print_r(debug_backtrace(), true));
			return $s;
		}

		/*
		*  // Placeholder for new rev of salmon which strips base64 padding.
		*  // PHP base64_decode handles the un-padded input without requiring this step
		*  // Uncomment if you find you need it.
		*
		*	$l = strlen($s);
		*	if (!strpos($s,'=')) {
		*		$m = $l % 4;
		*		if ($m == 2)
		*			$s .= '==';
		*		if ($m == 3)
		*			$s .= '=';
		*	}
		*
		*/

		return base64_decode(strtr($s, '-_', '+/'));
	}

	/**
	 * Normalize url
	 *
	 * @param string $url	URL to be normalized.
	 *
	 * @return string	Normalized URL.
	 */
	public static function normaliseLink($url)
	{
		$ret = str_replace(['https:', '//www.'], ['http:', '//'], $url);
		return rtrim($ret, '/');
	}

	/**
	 * Normalize OpenID identity
	 *
	 * @param string $s OpenID Identity
	 *
	 * @return string	normalized OpenId Identity
	 */
	public static function normaliseOpenID($s)
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
	public static function compareLink($a, $b)
	{
		return (strcasecmp(self::normaliseLink($a), self::normaliseLink($b)) === 0);
	}

	/**
	 * Ensures the provided URI has its query string punctuation in order.
	 *
	 * @param string $uri
	 * @return string
	 */
	public static function ensureQueryParameter($uri)
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
	 * @param array	 $chars
	 * @return bool
	 */
	public static function startsWithChars($string, array $chars)
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
	public static function startsWith(string $string, string $start)
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
	 * @return bool
	 */
	public static function endsWith(string $string, string $end)
	{
		$return = substr_compare($string, $end, -strlen($end)) === 0;

		return $return;
	}

	/**
	 * Returns the regular expression string to match URLs in a given text
	 *
	 * @return string
	 * @see https://daringfireball.net/2010/07/improved_regex_for_matching_urls
	 */
	public static function autoLinkRegEx()
	{
		return '@
(?<![=\'\]"/])			# Not preceded by [, =, \', ], ", /
\b
(							   # Capture 1: entire matched URL
  https?://							   # http or https protocol
  (?:
	[^/\s\xA0`!()\[\]{};:\'",<>?«»“”‘’.]	  # Domain can\'t start with a .
	[^/\s\xA0`!()\[\]{};:\'",<>?«»“”‘’]+	  # Domain can\'t end with a .
	\.
	[^/\s\xA0`!()\[\]{};:\'".,<>?«»“”‘’]+/? # Followed by a slash
  )
  (?:								   # One or more:
	[^\s\xA0()<>]+						   # Run of non-space, non-()<>
	|								   #   or
	\(([^\s\xA0()<>]+|(\([^\s()<>]+\)))*\) # balanced parens, up to 2 levels
	|								   #   or
	[^\s\xA0`!()\[\]{};:\'".,<>?«»“”‘’]	 # not a space or one of these punct chars
  )*
)@xiu';
	}

	/**
	 * Ensures a single path item doesn't contain any path-traversing characters
	 *
	 * @see https://stackoverflow.com/a/46097713
	 * @param string $pathItem
	 * @return string
	 */
	public static function sanitizeFilePathItem($pathItem)
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
	 * @return string
	 * @see substr_replace()
	 */
	public static function substringReplace(string $string, string $replacement, int $start, int $length = null)
	{
		$string_length = mb_strlen($string);

		$length = $length ?? $string_length;

		if ($start < 0) {
			$start = max(0, $string_length + $start);
		} else if ($start > $string_length) {
			$start = $string_length;
		}

		if ($length < 0) {
			$length = max(0, $string_length - $start + $length);
		} else if ($length > $string_length) {
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
	 * @return string
	 * @throws \Exception
	 */
	public static function performWithEscapedBlocks(string $text, string $regex, callable $callback)
	{
		// Enables nested use
		$executionId = random_int(PHP_INT_MAX / 10, PHP_INT_MAX);

		$blocks = [];

		$text = preg_replace_callback($regex,
			function ($matches) use ($executionId, &$blocks) {
				$return = '«block-' . $executionId . '-' . count($blocks) . '»';

				$blocks[] = $matches[0];

				return $return;
			},
			$text
		);

		$text = $callback($text) ?? '';

		// Restore code blocks
		$text = preg_replace_callback('/«block-' . $executionId . '-([0-9]+)»/iU',
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
}
