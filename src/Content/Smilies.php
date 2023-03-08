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

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\DI;
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
		$texts =  [
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

		$baseUrl = DI::baseUrl();

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
		'<img class="smiley" src="' . $baseUrl . '/images/smiley-embarassed.gif" alt=":-[" title=":-[" />',
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

		$params = ['texts' => $texts, 'icons' => $icons];
		Hook::callAll('smilie', $params);

		return $params;
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
		return strtr($subject, array_combine($search, $replace));
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
		if (intval(DI::config()->get('system', 'no_smilies'))
			|| (DI::userSession()->getLocalUserId() && intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'no_smilies')))
		) {
			return $text;
		}

		$text = preg_replace_callback('/<(pre)>(.*?)<\/pre>/ism', [self::class, 'encode'], $text);
		$text = preg_replace_callback('/<(code)>(.*?)<\/code>/ism', [self::class, 'encode'], $text);

		if ($no_images) {
			$cleaned = ['texts' => [], 'icons' => []];
			$icons = $smilies['icons'];
			foreach ($icons as $key => $icon) {
				if (!strstr($icon, '<img ')) {
					$cleaned['texts'][] = $smilies['texts'][$key];
					$cleaned['icons'][] = $smilies['icons'][$key];
				}
			}
			$smilies = $cleaned;
		}

		$text = preg_replace_callback('/&lt;(3+)/', [self::class, 'heartReplaceCallback'], $text);
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
		if (strlen($matches[1]) == 1) {
			return $matches[0];
		}

		$t = '';
		for ($cnt = 0; $cnt < strlen($matches[1]); $cnt ++) {
			$t .= '❤';
		}

		return str_replace($matches[0], $t, $matches[0]);
	}
}
