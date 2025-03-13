<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Content\Text\BBCode;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Util\Strings;

/**
 * This class contains functions to handle smiles
 */

class Smilies
{
	/**
	 * Replaces/adds the emoticon list
	 *
	 * This function should be used whenever emoticons are added
	 *
	 * @param array  $b              Array of emoticons
	 * @param string $smiley         The text smilie
	 * @param string $representation The replacement
	 * @return void
	 */
	public static function add(array &$b, string $smiley, string $representation)
	{
		$found = array_search($smiley, $b['texts']);

		if (!is_int($found)) {
			$b['texts'][] = $smiley;
			$b['icons'][] = $representation;
		} else {
			$b['icons'][$found] = $representation;
		}
	}

	/**
	 * Function to list all smilies
	 *
	 * Get an array of all smilies, both internal and from addons.
	 *
	 * @return array
	 *    'texts' => smilie shortcut
	 *    'icons' => icon in html
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @hook  smilie ('texts' => smilies texts array, 'icons' => smilies html array)
	 */
	public static function getList(): array
	{
		$texts = [
			'&lt;3',
			'&lt;/3',
			'&lt;\\3',
			':-)',
			';-)',
			':-(',
			':-P',
			':-p',
			':-"',
			':-&quot;',
			':-x',
			':-X',
			':-D',
			'8-|',
			'8-O',
			':-O',
			'\\o/',
			'o.O',
			'O.o',
			'o_O',
			'O_o',
			":'(",
			":-!",
			":-/",
			":-[",
			"8-)",
			':beer',
			':homebrew',
			':coffee',
			':facepalm',
			':like',
			':dislike',
			'~friendica',
			'red#',
			'red#matrix'

		];

		$baseUrl = (string) DI::baseUrl();

		$icons = [
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-heart.gif" alt="&lt;3" title="&lt;3" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-brokenheart.gif" alt="&lt;/3" title="&lt;/3" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-brokenheart.gif" alt="&lt;\\3" title="&lt;\\3" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-smile.gif" alt=":-)" title=":-)" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-wink.gif" alt=";-)" title=";-)" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-frown.gif" alt=":-(" title=":-(" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-tongue-out.gif" alt=":-P" title=":-P" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-tongue-out.gif" alt=":-p" title=":-P" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-kiss.gif" alt=":-\" title=":-\" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-kiss.gif" alt=":-\" title=":-\" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-kiss.gif" alt=":-x" title=":-x" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-kiss.gif" alt=":-X" title=":-X" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-laughing.gif" alt=":-D" title=":-D"  />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-surprised.gif" alt="8-|" title="8-|" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-surprised.gif" alt="8-O" title="8-O" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-surprised.gif" alt=":-O" title="8-O" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-thumbsup.gif" alt="\\o/" title="\\o/" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-Oo.gif" alt="o.O" title="o.O" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-Oo.gif" alt="O.o" title="O.o" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-Oo.gif" alt="o_O" title="o_O" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-Oo.gif" alt="O_o" title="O_o" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-cry.gif" alt=":\'(" title=":\'("/>',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-foot-in-mouth.gif" alt=":-!" title=":-!" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-undecided.gif" alt=":-/" title=":-/" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-embarrassed.gif" alt=":-[" title=":-[" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-cool.gif" alt="8-)" title="8-)" />',
			'<img class="smiley" src="' . $baseUrl . '/images/beer_mug.gif" alt=":beer" title=":beer" />',
			'<img class="smiley" src="' . $baseUrl . '/images/beer_mug.gif" alt=":homebrew" title=":homebrew" />',
			'<img class="smiley" src="' . $baseUrl . '/images/coffee.gif" alt=":coffee" title=":coffee" />',
			'<img class="smiley" src="' . $baseUrl . '/images/smiley-facepalm.gif" alt=":facepalm" title=":facepalm" />',
			'<img class="smiley" src="' . $baseUrl . '/images/like.gif" alt=":like" title=":like" />',
			'<img class="smiley" src="' . $baseUrl . '/images/dislike.gif" alt=":dislike" title=":dislike" />',
			'<a href="https://friendi.ca">~friendica <img class="smiley" width="16" height="16" src="' . $baseUrl . '/images/friendica.svg" alt="~friendica" title="~friendica" /></a>',
			'<a href="http://redmatrix.me/">red<img class="smiley" src="' . $baseUrl . '/images/rm-16.png" alt="red#" title="red#" />matrix</a>',
			'<a href="http://redmatrix.me/">red<img class="smiley" src="' . $baseUrl . '/images/rm-16.png" alt="red#matrix" title="red#matrix" />matrix</a>'
		];

		$eventDispatcher = DI::eventDispatcher();

		$params = ['texts' => $texts, 'icons' => $icons];

		$params = $eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::SMILEY_LIST, $params),
		)->getArray();

		return $params;
	}

	/**
	 * Finds all used smilies (denoted by quoting colons like :heart:) in the provided text and normalizes their usages.
	 *
	 * @param string $text that might contain smiley usages
	 * @return array with smilie codes (colon included) as the keys, their image urls as values;
	 *               the normalized string is put under the '' (empty string) key
	 */
	public static function extractUsedSmilies(string $text, string &$normalized = null): array
	{
		$emojis = [];

		$normalized = BBCode::performWithEscapedTags($text, ['code'], function ($text) use (&$emojis) {
			return BBCode::performWithEscapedTags($text, ['noparse', 'nobb', 'pre'], function ($text) use (&$emojis) {
				if (strpos($text, '[nosmile]') !== false || self::noSmilies()) {
					return $text;
				}
				$smilies    = self::getList();
				$normalized = [];
				return self::performForEachWordMatch(
					array_combine($smilies['texts'], $smilies['icons']),
					$text,
					function (string $name, string $image) use ($normalized, &$emojis) {
						if (array_key_exists($name, $normalized)) {
							return $normalized[$name];
						}
						if (preg_match('/src="(.+?)"/', $image, $match)) {
							$url = $match[1];
							// Image smilies, which should be normalized instead of being embedded for some protocols like ActivityPub.
							// Normalize name
							$norm = preg_replace('/[\s\-:#~]/', '', $name);
							if (!ctype_alnum($norm)) {
								if (preg_match('#/smiley-(\w+)\.gif#', $url, $match)) {
									$norm = $match[1];
								} else {
									$norm = 'smiley' . count($normalized);
								}
							}
							$shortcode         = ':' . $norm . ':';
							$normalized[$name] = $shortcode;
							$emojis[$norm]     = $url;
							return $shortcode;
						} else {
							$normalized[$name] = $image;
							// Probably text-substitution smilies (e.g., Unicode ones).
							return $image;
						}
					},
				);
			});
		});

		return $emojis;
	}

	/**
	 * Similar to strtr but matches only whole words and replaces texts with $callback.
	 *
	 * @param array $words
	 * @param string $subject
	 * @param callable $callback ($offset, $value)
	 * @return string
	 */
	private static function performForEachWordMatch(array $words, string $subject, callable $callback): string
	{
		$ord1_bitset = 0;
		$ord2_bitset = 0;
		$prefixes    = [];
		foreach ($words as $word => $_) {
			if (strlen($word) < 2) {
				continue;
			}
			$ord1 = ord($word[0]);
			$ord2 = ord($word[1]);
			// A smiley shortcode must not begin or end with whitespaces.
			if (ctype_space($word[0]) || ctype_space($word[strlen($word) - 1])) {
				continue;
			}
			$ord1_bitset |= 1 << ($ord1 & 31);
			$ord2_bitset |= 1 << ($ord2 & 31);
			if (!array_key_exists($word[0], $prefixes)) {
				$prefixes[$word[0]] = [];
			}
			$prefixes[$word[0]][] = $word;
		}

		$slength = strlen($subject);
		$result  = '';
		// $processed is used to delay string concatenation since appending a char every loop is inefficient.
		$processed = 0;
		// Find possible starting points for smilies.
		// For built-in smilies, the two bitsets should make attempts quite efficient.
		// However, presuming custom smilies follow the format of ":shortcode" or ":shortcode:",
		// if the user adds more smilies (with addons), the second bitset may eventually become useless.
		for ($i = 0; $i < $slength - 1; $i++) {
			$c = $subject[$i];
			$d = $subject[$i + 1];
			if (($ord1_bitset & (1 << (ord($c) & 31))) && ($ord2_bitset & (1 << (ord($d) & 31))) && array_key_exists($c, $prefixes)) {
				foreach ($prefixes[$c] as $word) {
					$wlength = strlen($word);
					if (substr($subject, $i, $wlength) === $word) {
						// Check for boundaries
						if (($i === 0 || ctype_space($subject[$i - 1]) || ctype_punct($subject[$i - 1]))
							&& ($i + $wlength >= $slength || ctype_space($subject[$i + $wlength]) || ctype_punct($subject[$i + $wlength]))) {
							$result .= substr($subject, $processed, $i - $processed);
							$result .= call_user_func($callback, $word, $words[$word]);
							$i += $wlength;
							$processed = $i;
							$i--;
							break;
						}
					}
				}
			}
		}
		if ($processed < $slength) {
			$result .= substr($subject, $processed);
		}
		return $result;
	}

	/**
	 * Copied from http://php.net/manual/en/function.str-replace.php#88569
	 * Modified for camel caps: renamed stro_replace -> strOrigReplace
	 *
	 * When using str_replace(...), values that did not exist in the original string (but were put there by previous
	 * replacements) will be replaced continuously.  This string replacement function is designed to replace the values
	 * in $search with those in $replace while not factoring in prior replacements.  Note that this function will
	 * always look for the longest possible match first and then work its way down to individual characters.
	 *
	 * @param array $search list of strings or characters that need to be replaced
	 * @param array $replace list of strings or characters that will replace the corresponding values in $search
	 * @param string $subject the string on which this operation is being performed
	 *
	 * @return string $subject with all substrings in the $search array replaced by the values in the $replace array
	 */
	private static function strOrigReplace(array $search, array $replace, string $subject): string
	{
		return self::performForEachWordMatch(
			array_combine($search, $replace),
			$subject,
			function (string $_, string $value) {
				return $value;
			}
		);
	}

	/**
	 * Replaces text emoticons with graphical images
	 *
	 * It is expected that this function will be called using HTML text.
	 * We will escape text between HTML pre and code blocks from being
	 * processed.
	 *
	 * At a higher level, the bbcode [nosmile] tag can be used to prevent this
	 * function from being executed by the prepare_text() routine when preparing
	 * bbcode source for HTML display
	 *
	 * @param string  $s         Text that should be replaced
	 * @param boolean $no_images Only replace emoticons without images
	 *
	 * @return string HTML Output of the Smilie
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function replace(string $s, bool $no_images = false): string
	{
		$smilies = self::getList();

		$s = self::replaceFromArray($s, $smilies, $no_images);

		return $s;
	}

	private static function noSmilies(): bool
	{
		return (intval(DI::config()->get('system', 'no_smilies')) ||
				(DI::userSession()->getLocalUserId() &&
				 intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'no_smilies'))));
	}

	/**
	 * Replaces emoji shortcodes in a string from a structured array of searches and replaces.
	 *
	 * Depends on system.no_smilies config value, skips <pre> and <code> tags.
	 *
	 * @param string $text      An HTML string
	 * @param array  $smilies   An string replacement array with the following structure: ['texts' => [], 'icons' => []]
	 * @param bool   $no_images Only replace shortcodes without image replacement (e.g. Unicode characters)
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function replaceFromArray(string $text, array $smilies, bool $no_images = false): string
	{
		if (self::noSmilies()) {
			return $text;
		}

		$text = preg_replace_callback('/<(pre)>(.*?)<\/pre>/ism', [self::class, 'encode'], $text);
		$text = preg_replace_callback('/<(code)>(.*?)<\/code>/ism', [self::class, 'encode'], $text);

		if ($no_images) {
			$cleaned = ['texts' => [], 'icons' => []];
			$icons   = $smilies['icons'];
			foreach ($icons as $key => $icon) {
				if (!strstr($icon, '<img ')) {
					$cleaned['texts'][] = $smilies['texts'][$key];
					$cleaned['icons'][] = $smilies['icons'][$key];
				}
			}
			$smilies = $cleaned;
		}

		$text = preg_replace_callback('/\B&lt;3+?\b/', [self::class, 'heartReplaceCallback'], $text);
		$text = self::strOrigReplace($smilies['texts'], $smilies['icons'], $text);

		$text = preg_replace_callback('/<(code)>(.*?)<\/code>/ism', [self::class, 'decode'], $text);
		$text = preg_replace_callback('/<(pre)>(.*?)<\/pre>/ism', [self::class, 'decode'], $text);

		return $text;
	}

	/**
	 * Encodes smiley match array to BASE64 string
	 *
	 * @param array $m Match array
	 * @return string base64 encoded string
	 */
	private static function encode(array $m): string
	{
		return '<' . $m[1] . '>' . Strings::base64UrlEncode($m[2]) . '</' . $m[1] . '>';
	}

	/**
	 * Decodes a previously BASE64-encoded match array to a string
	 *
	 * @param array $m Matches array
	 * @return string base64 decoded string
	 * @throws \Exception
	 */
	private static function decode(array $m): string
	{
		return '<' . $m[1] . '>' . Strings::base64UrlDecode($m[2]) . '</' . $m[1] . '>';
	}


	/**
	 * expand <3333 to the correct number of hearts
	 *
	 * @param array $matches
	 * @return string HTML Output
	 */
	private static function heartReplaceCallback(array $matches): string
	{
		return str_repeat('❤', strlen($matches[0]) - 4);
	}

	/**
	 * Checks if the body doesn't contain any alphanumeric characters
	 *
	 * @param string $body Possibly-HTML post body
	 * @return boolean
	 */
	public static function isEmojiPost(string $body): bool
	{
		// Strips all whitespace
		$conv = preg_replace('#\s#u', '', html_entity_decode($body));
		if (empty($conv)) {
			return false;
		}

		if (!class_exists('IntlChar')) {
			// Most Emojis are 4 byte Unicode characters, so this is a good workaround, when IntlChar does not exist on the system
			return strlen($conv) / mb_strlen($conv) == 4;
		}

		for ($i = 0; $i < mb_strlen($conv); $i++) {
			$character = mb_substr($conv, $i, 1);

			if (\IntlChar::isalnum($character) || \IntlChar::ispunct($character) || \IntlChar::isgraph($character) && (strlen($character) <= 2)) {
				return false;
			}
		}
		return true;
	}
}
