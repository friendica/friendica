<?php
/**
 * @file src/Content/Smilies.php
 * @brief This file contains the Smilies class which contains functions to handle smiles
 *
 * @todo Use the shortcodes from here:
 * https://github.com/iamcal/emoji-data/blob/master/emoji_pretty.json?raw=true
 * https://raw.githubusercontent.com/emojione/emojione/master/extras/alpha-codes/eac.json?raw=true
 * https://github.com/johannhof/emoji-helper/blob/master/data/emoji.json?raw=true
 *
 * Have also a look here:
 * https://www.webpagefx.com/tools/emoji-cheat-sheet/
 */
namespace Friendica\Content;

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;

/**
 * This class contains functions to handle smiles
 */

class Smilies
{
	/**
	 * @brief Replaces/adds the emoticon list
	 *
	 * This function should be used whenever emoticons are added
	 *
	 * @param array  $b              Array of emoticons
	 * @param string $smiley         The text smilie
	 * @param string $representation The replacement
	 *
	 * @return void
	 */
	public static function add(&$b, $smiley, $representation)
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
	 * @brief Function to list all smilies
	 *
	 * Get an array of all smilies, both internal and from addons.
	 *
	 * @return array
	 *	'texts' => smilie shortcut
	 *	'icons' => icon in html
	 *
	 * @hook smilie ('texts' => smilies texts array, 'icons' => smilies html array)
	 */
	public static function getList()
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

		$icons = [
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-heart.gif" alt="&lt;3" title="&lt;3" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-brokenheart.gif" alt="&lt;/3" title="&lt;/3" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-brokenheart.gif" alt="&lt;\\3" title="&lt;\\3" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-smile.gif" alt=":-)" title=":-)" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-wink.gif" alt=";-)" title=";-)" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-frown.gif" alt=":-(" title=":-(" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-tongue-out.gif" alt=":-P" title=":-P" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-tongue-out.gif" alt=":-p" title=":-P" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-kiss.gif" alt=":-\" title=":-\" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-kiss.gif" alt=":-\" title=":-\" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-kiss.gif" alt=":-x" title=":-x" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-kiss.gif" alt=":-X" title=":-X" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-laughing.gif" alt=":-D" title=":-D"  />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-surprised.gif" alt="8-|" title="8-|" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-surprised.gif" alt="8-O" title="8-O" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-surprised.gif" alt=":-O" title="8-O" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-thumbsup.gif" alt="\\o/" title="\\o/" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-Oo.gif" alt="o.O" title="o.O" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-Oo.gif" alt="O.o" title="O.o" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-Oo.gif" alt="o_O" title="o_O" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-Oo.gif" alt="O_o" title="O_o" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-cry.gif" alt=":\'(" title=":\'("/>',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-foot-in-mouth.gif" alt=":-!" title=":-!" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-undecided.gif" alt=":-/" title=":-/" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-embarassed.gif" alt=":-[" title=":-[" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-cool.gif" alt="8-)" title="8-)" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/beer_mug.gif" alt=":beer" title=":beer" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/beer_mug.gif" alt=":homebrew" title=":homebrew" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/coffee.gif" alt=":coffee" title=":coffee" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/smiley-facepalm.gif" alt=":facepalm" title=":facepalm" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/like.gif" alt=":like" title=":like" />',
		'<img class="smiley" src="' . System::baseUrl() . '/images/dislike.gif" alt=":dislike" title=":dislike" />',
		'<a href="https://friendi.ca">~friendica <img class="smiley" src="' . System::baseUrl() . '/images/friendica-16.png" alt="~friendica" title="~friendica" /></a>',
		'<a href="http://redmatrix.me/">red<img class="smiley" src="' . System::baseUrl() . '/images/rm-16.png" alt="red#" title="red#" />matrix</a>',
		'<a href="http://redmatrix.me/">red<img class="smiley" src="' . System::baseUrl() . '/images/rm-16.png" alt="red#matrix" title="red#matrix" />matrix</a>'
		];

		$params = ['texts' => $texts, 'icons' => $icons];
		Addon::callHooks('smilie', $params);

		return $params;
	}

	/**
	 * @brief Replaces text emoticons with graphical images
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
	 * @param boolean $sample    optional, default false
	 * @param boolean $no_images Only replace emoticons without images
	 *
	 * @return string HML Output of the Smilie
	 */
	public static function replace($s, $sample = false, $no_images = false)
	{
		if (intval(Config::get('system', 'no_smilies'))
			|| (local_user() && intval(PConfig::get(local_user(), 'system', 'no_smilies')))
		) {
			return $s;
		}

		$s = preg_replace_callback('/<pre>(.*?)<\/pre>/ism', 'self::encode', $s);
		$s = preg_replace_callback('/<code>(.*?)<\/code>/ism', 'self::encode', $s);

		$params = self::getList();

		if ($no_images) {
			$cleaned = ['texts' => [], 'icons' => []];
			$icons = $params['icons'];
			foreach ($icons as $key => $icon) {
				if (!strstr($icon, '<img ')) {
					$cleaned['texts'][] = $params['texts'][$key];
					$cleaned['icons'][] = $params['icons'][$key];
				}
			}
			$params = $cleaned;
		}

		$params['string'] = $s;

		if ($sample) {
			$s = '<div class="smiley-sample">';
			for ($x = 0; $x < count($params['texts']); $x ++) {
				$s .= '<dl><dt>' . $params['texts'][$x] . '</dt><dd>' . $params['icons'][$x] . '</dd></dl>';
			}
		} else {
			$params['string'] = preg_replace_callback('/&lt;(3+)/', 'self::pregHeart', $params['string']);
			$s = str_replace($params['texts'], $params['icons'], $params['string']);
		}

		$s = preg_replace_callback('/<pre>(.*?)<\/pre>/ism', 'self::decode', $s);
		$s = preg_replace_callback('/<code>(.*?)<\/code>/ism', 'self::decode', $s);

		return $s;
	}

	/**
	 * @param string $m string
	 *
	 * @return string base64 encoded string
	 */
	private static function encode($m)
	{
		return(str_replace($m[1], base64url_encode($m[1]), $m[0]));
	}

	/**
	 * @param string $m string
	 *
	 * @return string base64 decoded string
	 */
	private static function decode($m)
	{
		return(str_replace($m[1], base64url_decode($m[1]), $m[0]));
	}


	/**
	 * @brief expand <3333 to the correct number of hearts
	 *
	 * @param string $x string
	 *
	 * @return string HTML Output
	 *
	 * @todo: Rework because it doesn't work correctly
	 */
	private static function pregHeart($x)
	{
		if (strlen($x[1]) == 1) {
			return $x[0];
		}
		$t = '';
		for ($cnt = 0; $cnt < strlen($x[1]); $cnt ++) {
			$t .= '<img class="smiley" src="' . System::baseUrl() . '/images/smiley-heart.gif" alt="&lt;3" />';
		}
		$r =  str_replace($x[0], $t, $x[0]);
		return $r;
	}
}
