<?php
/**
 * @file include/text.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Smilies;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Render\FriendicaSmarty;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;

require_once "mod/proxy.php";
require_once "include/conversation.php";

/**
 * This is our template processor
 *
 * @param string|FriendicaSmarty $s the string requiring macro substitution,
 *				or an instance of FriendicaSmarty
 * @param array $r key value pairs (search => replace)
 * @return string substituted string
 */
function replace_macros($s, $r) {

	$stamp1 = microtime(true);

	$a = get_app();

	// pass $baseurl to all templates
	$r['$baseurl'] = System::baseUrl();

	$t = $a->template_engine();
	try {
		$output = $t->replaceMacros($s, $r);
	} catch (Exception $e) {
		echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
		killme();
	}

	$a->save_timestamp($stamp1, "rendering");

	return $output;
}

/**
 * @brief Generates a pseudo-random string of hexadecimal characters
 *
 * @param int $size
 * @return string
 */
function random_string($size = 64)
{
	$byte_size = ceil($size / 2);

	$bytes = random_bytes($byte_size);

	$return = substr(bin2hex($bytes), 0, $size);

	return $return;
}

/**
 * This is our primary input filter.
 *
 * The high bit hack only involved some old IE browser, forget which (IE5/Mac?)
 * that had an XSS attack vector due to stripping the high-bit on an 8-bit character
 * after cleansing, and angle chars with the high bit set could get through as markup.
 *
 * This is now disabled because it was interfering with some legitimate unicode sequences
 * and hopefully there aren't a lot of those browsers left.
 *
 * Use this on any text input where angle chars are not valid or permitted
 * They will be replaced with safer brackets. This may be filtered further
 * if these are not allowed either.
 *
 * @param string $string Input string
 * @return string Filtered string
 */
function notags($string) {
	return str_replace(["<", ">"], ['[', ']'], $string);

//  High-bit filter no longer used
//	return str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string);
}


/**
 * use this on "body" or "content" input where angle chars shouldn't be removed,
 * and allow them to be safely displayed.
 * @param string $string
 * @return string
 */
function escape_tags($string) {
	return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
}


/**
 * generate a string that's random, but usually pronounceable.
 * used to generate initial passwords
 * @param int $len
 * @return string
 */
function autoname($len) {

	if ($len <= 0) {
		return '';
	}

	$vowels = ['a','a','ai','au','e','e','e','ee','ea','i','ie','o','ou','u'];
	if (mt_rand(0, 5) == 4) {
		$vowels[] = 'y';
	}

	$cons = [
			'b','bl','br',
			'c','ch','cl','cr',
			'd','dr',
			'f','fl','fr',
			'g','gh','gl','gr',
			'h',
			'j',
			'k','kh','kl','kr',
			'l',
			'm',
			'n',
			'p','ph','pl','pr',
			'qu',
			'r','rh',
			's','sc','sh','sm','sp','st',
			't','th','tr',
			'v',
			'w','wh',
			'x',
			'z','zh'
			];

	$midcons = ['ck','ct','gn','ld','lf','lm','lt','mb','mm', 'mn','mp',
				'nd','ng','nk','nt','rn','rp','rt'];

	$noend = ['bl', 'br', 'cl','cr','dr','fl','fr','gl','gr',
				'kh', 'kl','kr','mn','pl','pr','rh','tr','qu','wh','q'];

	$start = mt_rand(0,2);
	if ($start == 0) {
		$table = $vowels;
	} else {
		$table = $cons;
	}

	$word = '';

	for ($x = 0; $x < $len; $x ++) {
		$r = mt_rand(0,count($table) - 1);
		$word .= $table[$r];

		if ($table == $vowels) {
			$table = array_merge($cons,$midcons);
		} else {
			$table = $vowels;
		}

	}

	$word = substr($word,0,$len);

	foreach ($noend as $noe) {
		$noelen = strlen($noe);
		if ((strlen($word) > $noelen) && (substr($word, -$noelen) == $noe)) {
			$word = autoname($len);
			break;
		}
	}

	return $word;
}


/**
 * escape text ($str) for XML transport
 * @param string $str
 * @return string Escaped text.
 */
function xmlify($str) {
	/// @TODO deprecated code found?
/*	$buffer = '';

	$len = mb_strlen($str);
	for ($x = 0; $x < $len; $x ++) {
		$char = mb_substr($str,$x,1);

		switch($char) {

			case "\r" :
				break;
			case "&" :
				$buffer .= '&amp;';
				break;
			case "'" :
				$buffer .= '&apos;';
				break;
			case "\"" :
				$buffer .= '&quot;';
				break;
			case '<' :
				$buffer .= '&lt;';
				break;
			case '>' :
				$buffer .= '&gt;';
				break;
			case "\n" :
				$buffer .= "\n";
				break;
			default :
				$buffer .= $char;
				break;
		}
	}*/
	/*
	$buffer = mb_ereg_replace("&", "&amp;", $str);
	$buffer = mb_ereg_replace("'", "&apos;", $buffer);
	$buffer = mb_ereg_replace('"', "&quot;", $buffer);
	$buffer = mb_ereg_replace("<", "&lt;", $buffer);
	$buffer = mb_ereg_replace(">", "&gt;", $buffer);
	*/
	$buffer = htmlspecialchars($str, ENT_QUOTES, "UTF-8");
	$buffer = trim($buffer);

	return $buffer;
}


/**
 * undo an xmlify
 * @param string $s xml escaped text
 * @return string unescaped text
 */
function unxmlify($s) {
	/// @TODO deprecated code found?
//	$ret = str_replace('&amp;','&', $s);
//	$ret = str_replace(array('&lt;','&gt;','&quot;','&apos;'),array('<','>','"',"'"),$ret);
	/*$ret = mb_ereg_replace('&amp;', '&', $s);
	$ret = mb_ereg_replace('&apos;', "'", $ret);
	$ret = mb_ereg_replace('&quot;', '"', $ret);
	$ret = mb_ereg_replace('&lt;', "<", $ret);
	$ret = mb_ereg_replace('&gt;', ">", $ret);
	*/
	$ret = htmlspecialchars_decode($s, ENT_QUOTES);
	return $ret;
}


/**
 * @brief Paginator function. Pushes relevant links in a pager array structure.
 *
 * Links are generated depending on the current page and the total number of items.
 * Inactive links (like "first" and "prev" on page 1) are given the "disabled" class.
 * Current page link is given the "active" CSS class
 *
 * @param App $a App instance
 * @param int $count [optional] item count (used with minimal pager)
 * @return Array data for pagination template
 */
function paginate_data(App $a, $count = null) {
	$stripped = preg_replace('/([&?]page=[0-9]*)/', '', $a->query_string);

	$stripped = str_replace('q=', '', $stripped);
	$stripped = trim($stripped, '/');
	$pagenum = $a->pager['page'];

	if (($a->page_offset != '') && !preg_match('/[?&].offset=/', $stripped)) {
		$stripped .= '&offset=' . urlencode($a->page_offset);
	}

	$url = $stripped;
	$data = [];

	function _l(&$d, $name, $url, $text, $class = '') {
		if (strpos($url, '?') === false && ($pos = strpos($url, '&')) !== false) {
			$url = substr($url, 0, $pos) . '?' . substr($url, $pos + 1);
		}

		$d[$name] = ['url' => $url, 'text' => $text, 'class' => $class];
	}

	if (!is_null($count)) {
		// minimal pager (newer / older)
		$data['class'] = 'pager';
		_l($data, 'prev', $url . '&page=' . ($a->pager['page'] - 1), L10n::t('newer'), 'previous' . ($a->pager['page'] == 1 ? ' disabled' : ''));
		_l($data, 'next', $url . '&page=' . ($a->pager['page'] + 1), L10n::t('older'), 'next' . ($count <= 0 ? ' disabled' : ''));
	} else {
		// full pager (first / prev / 1 / 2 / ... / 14 / 15 / next / last)
		$data['class'] = 'pagination';
		if ($a->pager['total'] > $a->pager['itemspage']) {
			_l($data, 'first', $url . '&page=1', L10n::t('first'), $a->pager['page'] == 1 ? 'disabled' : '');
			_l($data, 'prev', $url . '&page=' . ($a->pager['page'] - 1), L10n::t('prev'), $a->pager['page'] == 1 ? 'disabled' : '');

			$numpages = $a->pager['total'] / $a->pager['itemspage'];

			$numstart = 1;
			$numstop = $numpages;

			// Limit the number of displayed page number buttons.
			if ($numpages > 8) {
				$numstart = (($pagenum > 4) ? ($pagenum - 4) : 1);
				$numstop = (($pagenum > ($numpages - 7)) ? $numpages : ($numstart + 8));
			}

			$pages = [];

			for ($i = $numstart; $i <= $numstop; $i++) {
				if ($i == $a->pager['page']) {
					_l($pages, $i, '#',  $i, 'current active');
				} else {
					_l($pages, $i, $url . '&page='. $i, $i, 'n');
				}
			}

			if (($a->pager['total'] % $a->pager['itemspage']) != 0) {
				if ($i == $a->pager['page']) {
					_l($pages, $i, '#',  $i, 'current active');
				} else {
					_l($pages, $i, $url . '&page=' . $i, $i, 'n');
				}
			}

			$data['pages'] = $pages;

			$lastpage = (($numpages > intval($numpages)) ? intval($numpages)+1 : $numpages);
			_l($data, 'next', $url . '&page=' . ($a->pager['page'] + 1), L10n::t('next'), $a->pager['page'] == $lastpage ? 'disabled' : '');
			_l($data, 'last', $url . '&page=' . $lastpage, L10n::t('last'), $a->pager['page'] == $lastpage ? 'disabled' : '');
		}
	}

	return $data;
}


/**
 * Automatic pagination.
 *
 *  To use, get the count of total items.
 * Then call $a->set_pager_total($number_items);
 * Optionally call $a->set_pager_itemspage($n) to the number of items to display on each page
 * Then call paginate($a) after the end of the display loop to insert the pager block on the page
 * (assuming there are enough items to paginate).
 * When using with SQL, the setting LIMIT %d, %d => $a->pager['start'],$a->pager['itemspage']
 * will limit the results to the correct items for the current page.
 * The actual page handling is then accomplished at the application layer.
 *
 * @param App $a App instance
 * @return string html for pagination #FIXME remove html
 */
function paginate(App $a) {

	$data = paginate_data($a);
	$tpl = get_markup_template("paginate.tpl");
	return replace_macros($tpl, ["pager" => $data]);

}


/**
 * Alternative pager
 * @param App $a App instance
 * @param int $i
 * @return string html for pagination #FIXME remove html
 */
function alt_pager(App $a, $i) {

	$data = paginate_data($a, $i);
	$tpl = get_markup_template("paginate.tpl");
	return replace_macros($tpl, ['pager' => $data]);

}


/**
 * Loader for infinite scrolling
 * @return string html for loader
 */
function scroll_loader() {
	$tpl = get_markup_template("scroll_loader.tpl");
	return replace_macros($tpl, [
		'wait' => L10n::t('Loading more entries...'),
		'end' => L10n::t('The end')
	]);
}


/**
 * Turn user/group ACLs stored as angle bracketed text into arrays
 *
 * @param string $s
 * @return array
 */
function expand_acl($s) {
	// turn string array of angle-bracketed elements into numeric array
	// e.g. "<1><2><3>" => array(1,2,3);
	$ret = [];

	if (strlen($s)) {
		$t = str_replace('<', '', $s);
		$a = explode('>', $t);
		foreach ($a as $aa) {
			if (intval($aa)) {
				$ret[] = intval($aa);
			}
		}
	}
	return $ret;
}


/**
 * Wrap ACL elements in angle brackets for storage
 * @param string $item
 */
function sanitise_acl(&$item) {
	if (intval($item)) {
		$item = '<' . intval(notags(trim($item))) . '>';
	} else {
		unset($item);
	}
}


/**
 * Convert an ACL array to a storable string
 *
 * Normally ACL permissions will be an array.
 * We'll also allow a comma-separated string.
 *
 * @param string|array $p
 * @return string
 */
function perms2str($p) {
	$ret = '';
	if (is_array($p)) {
		$tmp = $p;
	} else {
		$tmp = explode(',', $p);
	}

	if (is_array($tmp)) {
		array_walk($tmp, 'sanitise_acl');
		$ret = implode('', $tmp);
	}
	return $ret;
}

/**
 * @deprecated
 * wrapper to load a view template, checking for alternate
 * languages before falling back to the default
 *
 * @global string $lang
 * @global App $a
 * @param string $s view name
 * @return string
 */
function load_view_file($s) {
	global $lang, $a;
	if (!isset($lang)) {
		$lang = 'en';
	}
	$b = basename($s);
	$d = dirname($s);
	if (file_exists("$d/$lang/$b")) {
		$stamp1 = microtime(true);
		$content = file_get_contents("$d/$lang/$b");
		$a->save_timestamp($stamp1, "file");
		return $content;
	}

	$theme = $a->getCurrentTheme();

	if (file_exists("$d/theme/$theme/$b")) {
		$stamp1 = microtime(true);
		$content = file_get_contents("$d/theme/$theme/$b");
		$a->save_timestamp($stamp1, "file");
		return $content;
	}

	$stamp1 = microtime(true);
	$content = file_get_contents($s);
	$a->save_timestamp($stamp1, "file");
	return $content;
}


/**
 * load a view template, checking for alternate
 * languages before falling back to the default
 *
 * @global string $lang
 * @param string $s view path
 * @return string
 */
function get_intltext_template($s) {
	global $lang;

	$a = get_app();
	$engine = '';
	if ($a->theme['template_engine'] === 'smarty3') {
		$engine = "/smarty3";
	}

	if (!isset($lang)) {
		$lang = 'en';
	}

	if (file_exists("view/lang/$lang$engine/$s")) {
		$stamp1 = microtime(true);
		$content = file_get_contents("view/lang/$lang$engine/$s");
		$a->save_timestamp($stamp1, "file");
		return $content;
	} elseif (file_exists("view/lang/en$engine/$s")) {
		$stamp1 = microtime(true);
		$content = file_get_contents("view/lang/en$engine/$s");
		$a->save_timestamp($stamp1, "file");
		return $content;
	} else {
		$stamp1 = microtime(true);
		$content = file_get_contents("view$engine/$s");
		$a->save_timestamp($stamp1, "file");
		return $content;
	}
}


/**
 * load template $s
 *
 * @param string $s
 * @param string $root
 * @return string
 */
function get_markup_template($s, $root = '') {
	$stamp1 = microtime(true);

	$a = get_app();
	$t = $a->template_engine();
	try {
		$template = $t->getTemplateFile($s, $root);
	} catch (Exception $e) {
		echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
		killme();
	}

	$a->save_timestamp($stamp1, "file");

	return $template;
}

/**
 *  for html,xml parsing - let's say you've got
 *  an attribute foobar="class1 class2 class3"
 *  and you want to find out if it contains 'class3'.
 *  you can't use a normal sub string search because you
 *  might match 'notclass3' and a regex to do the job is
 *  possible but a bit complicated.
 *  pass the attribute string as $attr and the attribute you
 *  are looking for as $s - returns true if found, otherwise false
 *
 * @param string $attr attribute value
 * @param string $s string to search
 * @return boolean True if found, False otherwise
 */
function attribute_contains($attr, $s) {
	$a = explode(' ', $attr);
	return (count($a) && in_array($s,$a));
}


/* setup int->string log level map */
$LOGGER_LEVELS = [];

/**
 * @brief Logs the given message at the given log level
 *
 * log levels:
 * LOGGER_NORMAL (default)
 * LOGGER_TRACE
 * LOGGER_DEBUG
 * LOGGER_DATA
 * LOGGER_ALL
 *
 * @global App $a
 * @global array $LOGGER_LEVELS
 * @param string $msg
 * @param int $level
 */
function logger($msg, $level = 0) {
	$a = get_app();
	global $LOGGER_LEVELS;

	// turn off logger in install mode
	if (
		$a->mode == App::MODE_INSTALL
		|| !dba::$connected
	) {
		return;
	}

	$debugging = Config::get('system','debugging');
	$logfile   = Config::get('system','logfile');
	$loglevel = intval(Config::get('system','loglevel'));

	if (
		!$debugging
		|| !$logfile
		|| $level > $loglevel
	) {
		return;
	}

	if (count($LOGGER_LEVELS) == 0) {
		foreach (get_defined_constants() as $k => $v) {
			if (substr($k, 0, 7) == "LOGGER_") {
				$LOGGER_LEVELS[$v] = substr($k, 7, 7);
			}
		}
	}

	$process_id = session_id();

	if ($process_id == '') {
		$process_id = get_app()->process_id;
	}

	$callers = debug_backtrace();

	if (count($callers) > 1) {
		$function = $callers[1]['function'];
	} else {
		$function = '';
	}

	$logline = sprintf("%s@%s\t[%s]:%s:%s:%s\t%s\n",
			DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			$process_id,
			$LOGGER_LEVELS[$level],
			basename($callers[0]['file']),
			$callers[0]['line'],
			$function,
			$msg
		);

	$stamp1 = microtime(true);
	@file_put_contents($logfile, $logline, FILE_APPEND);
	$a->save_timestamp($stamp1, "file");
}

/**
 * @brief An alternative logger for development.
 * Works largely as logger() but allows developers
 * to isolate particular elements they are targetting
 * personally without background noise
 *
 * log levels:
 * LOGGER_NORMAL (default)
 * LOGGER_TRACE
 * LOGGER_DEBUG
 * LOGGER_DATA
 * LOGGER_ALL
 *
 * @global App $a
 * @global array $LOGGER_LEVELS
 * @param string $msg
 * @param int $level
 */

function dlogger($msg, $level = 0) {
	$a = get_app();

	// turn off logger in install mode
	if (
		$a->mode == App::MODE_INSTALL
		|| !dba::$connected
	) {
		return;
	}

	$logfile = Config::get('system', 'dlogfile');
	if (!$logfile) {
		return;
	}

	$dlogip = Config::get('system', 'dlogip');
	if (!is_null($dlogip) && $_SERVER['REMOTE_ADDR'] != $dlogip) {
		return;
	}

	if (count($LOGGER_LEVELS) == 0) {
		foreach (get_defined_constants() as $k => $v) {
			if (substr($k, 0, 7) == "LOGGER_") {
				$LOGGER_LEVELS[$v] = substr($k, 7, 7);
			}
		}
	}

	$process_id = session_id();

	if ($process_id == '') {
		$process_id = get_app()->process_id;
	}

	$callers = debug_backtrace();
	$logline = sprintf("%s@\t%s:\t%s:\t%s\t%s\t%s\n",
			DateTimeFormat::utcNow(),
			$process_id,
			basename($callers[0]['file']),
			$callers[0]['line'],
			$callers[1]['function'],
			$msg
		);

	$stamp1 = microtime(true);
	@file_put_contents($logfile, $logline, FILE_APPEND);
	$a->save_timestamp($stamp1, "file");
}


/**
 * Compare activity uri. Knows about activity namespace.
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function activity_match($haystack,$needle) {
	return (($haystack === $needle) || ((basename($needle) === $haystack) && strstr($needle, NAMESPACE_ACTIVITY_SCHEMA)));
}


/**
 * @brief Pull out all #hashtags and @person tags from $string.
 *
 * We also get @person@domain.com - which would make
 * the regex quite complicated as tags can also
 * end a sentence. So we'll run through our results
 * and strip the period from any tags which end with one.
 * Returns array of tags found, or empty array.
 *
 * @param string $string Post content
 * @return array List of tag and person names
 */
function get_tags($string) {
	$ret = [];

	// Convert hashtag links to hashtags
	$string = preg_replace('/#\[url\=([^\[\]]*)\](.*?)\[\/url\]/ism', '#$2', $string);

	// ignore anything in a code block
	$string = preg_replace('/\[code\](.*?)\[\/code\]/sm', '', $string);

	// Force line feeds at bbtags
	$string = str_replace(['[', ']'], ["\n[", "]\n"], $string);

	// ignore anything in a bbtag
	$string = preg_replace('/\[(.*?)\]/sm', '', $string);

	// Match full names against @tags including the space between first and last
	// We will look these up afterward to see if they are full names or not recognisable.

	if (preg_match_all('/(@[^ \x0D\x0A,:?]+ [^ \x0D\x0A@,:?]+)([ \x0D\x0A@,:?]|$)/', $string, $matches)) {
		foreach ($matches[1] as $match) {
			if (strstr($match, ']')) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if (substr($match, -1, 1) === '.') {
				$ret[] = substr($match, 0, -1);
			} else {
				$ret[] = $match;
			}
		}
	}

	// Otherwise pull out single word tags. These can be @nickname, @first_last
	// and #hash tags.

	if (preg_match_all('/([!#@][^\^ \x0D\x0A,;:?]+)([ \x0D\x0A,;:?]|$)/', $string, $matches)) {
		foreach ($matches[1] as $match) {
			if (strstr($match, ']')) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if (substr($match, -1, 1) === '.') {
				$match = substr($match,0,-1);
			}
			// ignore strictly numeric tags like #1
			if ((strpos($match, '#') === 0) && ctype_digit(substr($match, 1))) {
				continue;
			}
			// try not to catch url fragments
			if (strpos($string, $match) && preg_match('/[a-zA-z0-9\/]/', substr($string, strpos($string, $match) - 1, 1))) {
				continue;
			}
			$ret[] = $match;
		}
	}
	return $ret;
}


/**
 * quick and dirty quoted_printable encoding
 *
 * @param string $s
 * @return string
 */
function qp($s) {
	return str_replace("%", "=", rawurlencode($s));
}


/**
 * Get html for contact block.
 *
 * @template contact_block.tpl
 * @hook contact_block_end (contacts=>array, output=>string)
 * @return string
 */
function contact_block() {
	$o = '';
	$a = get_app();

	$shown = PConfig::get($a->profile['uid'], 'system', 'display_friend_count', 24);
	if ($shown == 0) {
		return;
	}

	if (!is_array($a->profile) || $a->profile['hide-friends']) {
		return $o;
	}
	$r = q("SELECT COUNT(*) AS `total` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked`
				AND NOT `pending` AND NOT `hidden` AND NOT `archive`
				AND `network` IN ('%s', '%s', '%s')",
			intval($a->profile['uid']),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_OSTATUS),
			dbesc(NETWORK_DIASPORA)
	);
	if (DBM::is_result($r)) {
		$total = intval($r[0]['total']);
	}
	if (!$total) {
		$contacts = L10n::t('No contacts');
		$micropro = null;
	} else {
		// Splitting the query in two parts makes it much faster
		$r = q("SELECT `id` FROM `contact`
				WHERE `uid` = %d AND NOT `self` AND NOT `blocked`
					AND NOT `pending` AND NOT `hidden` AND NOT `archive`
					AND `network` IN ('%s', '%s', '%s')
				ORDER BY RAND() LIMIT %d",
				intval($a->profile['uid']),
				dbesc(NETWORK_DFRN),
				dbesc(NETWORK_OSTATUS),
				dbesc(NETWORK_DIASPORA),
				intval($shown)
		);
		if (DBM::is_result($r)) {
			$contacts = [];
			foreach ($r AS $contact) {
				$contacts[] = $contact["id"];
			}
			$r = q("SELECT `id`, `uid`, `addr`, `url`, `name`, `thumb`, `network` FROM `contact` WHERE `id` IN (%s)",
				dbesc(implode(",", $contacts)));

			if (DBM::is_result($r)) {
				$contacts = L10n::tt('%d Contact', '%d Contacts', $total);
				$micropro = [];
				foreach ($r as $rr) {
					$micropro[] = micropro($rr, true, 'mpfriend');
				}
			}
		}
	}

	$tpl = get_markup_template('contact_block.tpl');
	$o = replace_macros($tpl, [
		'$contacts' => $contacts,
		'$nickname' => $a->profile['nickname'],
		'$viewcontacts' => L10n::t('View Contacts'),
		'$micropro' => $micropro,
	]);

	$arr = ['contacts' => $r, 'output' => $o];

	Addon::callHooks('contact_block_end', $arr);
	return $o;

}


/**
 * @brief Format contacts as picture links or as texxt links
 *
 * @param array $contact Array with contacts which contains an array with
 *	int 'id' => The ID of the contact
 *	int 'uid' => The user ID of the user who owns this data
 *	string 'name' => The name of the contact
 *	string 'url' => The url to the profile page of the contact
 *	string 'addr' => The webbie of the contact (e.g.) username@friendica.com
 *	string 'network' => The network to which the contact belongs to
 *	string 'thumb' => The contact picture
 *	string 'click' => js code which is performed when clicking on the contact
 * @param boolean $redirect If true try to use the redir url if it's possible
 * @param string $class CSS class for the
 * @param boolean $textmode If true display the contacts as text links
 *	if false display the contacts as picture links

 * @return string Formatted html
 */
function micropro($contact, $redirect = false, $class = '', $textmode = false) {

	// Use the contact URL if no address is available
	if (!x($contact, "addr")) {
		$contact["addr"] = $contact["url"];
	}

	$url = $contact['url'];
	$sparkle = '';
	$redir = false;

	if ($redirect) {
		$url = Contact::magicLink($contact['url']);
		if (strpos($url, 'redir/') === 0) {
			$sparkle = ' sparkle';
		}
	}

	// If there is some js available we don't need the url
	if (x($contact, 'click')) {
		$url = '';
	}

	return replace_macros(get_markup_template(($textmode)?'micropro_txt.tpl':'micropro_img.tpl'),[
		'$click' => defaults($contact, 'click', ''),
		'$class' => $class,
		'$url' => $url,
		'$photo' => proxy_url($contact['thumb'], false, PROXY_SIZE_THUMB),
		'$name' => $contact['name'],
		'title' => $contact['name'] . ' [' . $contact['addr'] . ']',
		'$parkle' => $sparkle,
		'$redir' => $redir,

	]);
}

/**
 * Search box.
 *
 * @param string $s     Search query.
 * @param string $id    HTML id
 * @param string $url   Search url.
 * @param bool   $save  Show save search button.
 * @param bool   $aside Display the search widgit aside.
 *
 * @return string Formatted HTML.
 */
function search($s, $id = 'search-box', $url = 'search', $save = false, $aside = true)
{
	$mode = 'text';

	if (strpos($s, '#') === 0) {
		$mode = 'tag';
	}
	$save_label = $mode === 'text' ? L10n::t('Save') : L10n::t('Follow');

	$values = [
			'$s' => htmlspecialchars($s),
			'$id' => $id,
			'$action_url' => $url,
			'$search_label' => L10n::t('Search'),
			'$save_label' => $save_label,
			'$savedsearch' => local_user() && Feature::isEnabled(local_user(),'savedsearch'),
			'$search_hint' => L10n::t('@name, !forum, #tags, content'),
			'$mode' => $mode
		];

	if (!$aside) {
		$values['$searchoption'] = [
					L10n::t("Full Text"),
					L10n::t("Tags"),
					L10n::t("Contacts")];

		if (Config::get('system','poco_local_search')) {
			$values['$searchoption'][] = L10n::t("Forums");
		}
	}

	return replace_macros(get_markup_template('searchbox.tpl'), $values);
}

/**
 * @brief Check for a valid email string
 *
 * @param string $email_address
 * @return boolean
 */
function valid_email($email_address)
{
	return preg_match('/^[_a-zA-Z0-9\-\+]+(\.[_a-zA-Z0-9\-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', $email_address);
}


/**
 * Replace naked text hyperlink with HTML formatted hyperlink
 *
 * @param string $s
 */
function linkify($s) {
	$s = preg_replace("/(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\'\%\$\!\+]*)/", ' <a href="$1" target="_blank">$1</a>', $s);
	$s = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$s);
	return $s;
}


/**
 * Load poke verbs
 *
 * @return array index is present tense verb
 * 				 value is array containing past tense verb, translation of present, translation of past
 * @hook poke_verbs pokes array
 */
function get_poke_verbs() {

	// index is present tense verb
	// value is array containing past tense verb, translation of present, translation of past

	$arr = [
		'poke' => ['poked', L10n::t('poke'), L10n::t('poked')],
		'ping' => ['pinged', L10n::t('ping'), L10n::t('pinged')],
		'prod' => ['prodded', L10n::t('prod'), L10n::t('prodded')],
		'slap' => ['slapped', L10n::t('slap'), L10n::t('slapped')],
		'finger' => ['fingered', L10n::t('finger'), L10n::t('fingered')],
		'rebuff' => ['rebuffed', L10n::t('rebuff'), L10n::t('rebuffed')],
	];
	Addon::callHooks('poke_verbs', $arr);
	return $arr;
}

/**
 * @brief Translate days and months names.
 *
 * @param string $s String with day or month name.
 * @return string Translated string.
 */
function day_translate($s) {
	$ret = str_replace(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
		[L10n::t('Monday'), L10n::t('Tuesday'), L10n::t('Wednesday'), L10n::t('Thursday'), L10n::t('Friday'), L10n::t('Saturday'), L10n::t('Sunday')],
		$s);

	$ret = str_replace(['January','February','March','April','May','June','July','August','September','October','November','December'],
		[L10n::t('January'), L10n::t('February'), L10n::t('March'), L10n::t('April'), L10n::t('May'), L10n::t('June'), L10n::t('July'), L10n::t('August'), L10n::t('September'), L10n::t('October'), L10n::t('November'), L10n::t('December')],
		$ret);

	return $ret;
}

/**
 * @brief Translate short days and months names.
 *
 * @param string $s String with short day or month name.
 * @return string Translated string.
 */
function day_short_translate($s) {
	$ret = str_replace(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
		[L10n::t('Mon'), L10n::t('Tue'), L10n::t('Wed'), L10n::t('Thu'), L10n::t('Fri'), L10n::t('Sat'), L10n::t('Sun')],
		$s);
	$ret = str_replace(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov','Dec'],
		[L10n::t('Jan'), L10n::t('Feb'), L10n::t('Mar'), L10n::t('Apr'), L10n::t('May'), ('Jun'), L10n::t('Jul'), L10n::t('Aug'), L10n::t('Sep'), L10n::t('Oct'), L10n::t('Nov'), L10n::t('Dec')],
		$ret);
	return $ret;
}


/**
 * Normalize url
 *
 * @param string $url
 * @return string
 */
function normalise_link($url) {
	$ret = str_replace(['https:', '//www.'], ['http:', '//'], $url);
	return rtrim($ret,'/');
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
function link_compare($a, $b) {
	return (strcasecmp(normalise_link($a), normalise_link($b)) === 0);
}


/**
 * @brief Find any non-embedded images in private items and add redir links to them
 *
 * @param App $a
 * @param array &$item The field array of an item row
 */
function redir_private_images($a, &$item)
{
	$matches = false;
	$cnt = preg_match_all('|\[img\](http[^\[]*?/photo/[a-fA-F0-9]+?(-[0-9]\.[\w]+?)?)\[\/img\]|', $item['body'], $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (strpos($mtch[1], '/redir') !== false) {
				continue;
			}

			if ((local_user() == $item['uid']) && ($item['private'] != 0) && ($item['contact-id'] != $a->contact['id']) && ($item['network'] == NETWORK_DFRN)) {
				$img_url = 'redir?f=1&quiet=1&url=' . urlencode($mtch[1]) . '&conurl=' . urlencode($item['author-link']);
				$item['body'] = str_replace($mtch[0], '[img]' . $img_url . '[/img]', $item['body']);
			}
		}
	}
}

/**
 * Sets the "rendered-html" field of the provided item
 *
 * Body is preserved to avoid side-effects as we modify it just-in-time for spoilers and private image links
 *
 * @param array $item
 * @param bool  $update
 *
 * @todo Remove reference, simply return "rendered-html" and "rendered-hash"
 */
function put_item_in_cache(&$item, $update = false)
{
	$body = $item["body"];

	$rendered_hash = defaults($item, 'rendered-hash', '');
	$rendered_html = defaults($item, 'rendered-html', '');

	if ($rendered_hash == ''
		|| $rendered_html == ""
		|| $rendered_hash != hash("md5", $item["body"])
		|| Config::get("system", "ignore_cache")
	) {
		$a = get_app();
		redir_private_images($a, $item);

		$item["rendered-html"] = prepare_text($item["body"]);
		$item["rendered-hash"] = hash("md5", $item["body"]);

		// Force an update if the generated values differ from the existing ones
		if ($rendered_hash != $item["rendered-hash"]) {
			$update = true;
		}

		// Only compare the HTML when we forcefully ignore the cache
		if (Config::get("system", "ignore_cache") && ($rendered_html != $item["rendered-html"])) {
			$update = true;
		}

		if ($update && !empty($item["id"])) {
			Item::update(['rendered-html' => $item["rendered-html"], 'rendered-hash' => $item["rendered-hash"]],
					['id' => $item["id"]]);
		}
	}

	$item["body"] = $body;
}

/**
 * @brief Given an item array, convert the body element from bbcode to html and add smilie icons.
 * If attach is true, also add icons for item attachments.
 *
 * @param array   $item
 * @param boolean $attach
 * @param boolean $is_preview
 * @return string item body html
 * @hook prepare_body_init item array before any work
 * @hook prepare_body_content_filter ('item'=>item array, 'filter_reasons'=>string array) before first bbcode to html
 * @hook prepare_body ('item'=>item array, 'html'=>body string, 'is_preview'=>boolean, 'filter_reasons'=>string array) after first bbcode to html
 * @hook prepare_body_final ('item'=>item array, 'html'=>body string) after attach icons and blockquote special case handling (spoiler, author)
 */
function prepare_body(array &$item, $attach = false, $is_preview = false)
{
	$a = get_app();
	Addon::callHooks('prepare_body_init', $item);

	// In order to provide theme developers more possibilities, event items
	// are treated differently.
	if ($item['object-type'] === ACTIVITY_OBJ_EVENT && isset($item['event-id'])) {
		$ev = Event::getItemHTML($item);
		return $ev;
	}

	$tags = \Friendica\Model\Term::populateTagsFromItem($item);

	$item['tags'] = $tags['tags'];
	$item['hashtags'] = $tags['hashtags'];
	$item['mentions'] = $tags['mentions'];

	// Compile eventual content filter reasons
	$filter_reasons = [];
	if (!$is_preview && public_contact() != $item['author-id']) {
		if (!empty($item['content-warning']) && (!local_user() || !PConfig::get(local_user(), 'system', 'disable_cw', false))) {
			$filter_reasons[] = L10n::t('Content warning: %s', $item['content-warning']);
		}

		$hook_data = [
			'item' => $item,
			'filter_reasons' => $filter_reasons
		];
		Addon::callHooks('prepare_body_content_filter', $hook_data);
		$filter_reasons = $hook_data['filter_reasons'];
		unset($hook_data);
	}

	// Update the cached values if there is no "zrl=..." on the links.
	$update = (!local_user() && !remote_user() && ($item["uid"] == 0));

	// Or update it if the current viewer is the intented viewer.
	if (($item["uid"] == local_user()) && ($item["uid"] != 0)) {
		$update = true;
	}

	put_item_in_cache($item, $update);
	$s = $item["rendered-html"];

	$hook_data = [
		'item' => $item,
		'html' => $s,
		'preview' => $is_preview,
		'filter_reasons' => $filter_reasons
	];
	Addon::callHooks('prepare_body', $hook_data);
	$s = $hook_data['html'];
	unset($hook_data);

	if (!$attach) {
		// Replace the blockquotes with quotes that are used in mails.
		$mailquote = '<blockquote type="cite" class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px #ccc solid;padding-left:1ex;">';
		$s = str_replace(['<blockquote>', '<blockquote class="spoiler">', '<blockquote class="author">'], [$mailquote, $mailquote, $mailquote], $s);
		return $s;
	}

	$as = '';
	$vhead = false;
	$matches = [];
	preg_match_all('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\"(?: title=\"(.*?)\")?|', $item['attach'], $matches, PREG_SET_ORDER);
	foreach ($matches as $mtch) {
		$mime = $mtch[3];

		$the_url = Contact::magicLinkById($item['author-id'], $mtch[1]);

		if (strpos($mime, 'video') !== false) {
			if (!$vhead) {
				$vhead = true;
				$a->page['htmlhead'] .= replace_macros(get_markup_template('videos_head.tpl'), [
					'$baseurl' => System::baseUrl(),
				]);
				$a->page['end'] .= replace_macros(get_markup_template('videos_end.tpl'), [
					'$baseurl' => System::baseUrl(),
				]);
			}

			$url_parts = explode('/', $the_url);
			$id = end($url_parts);
			$as .= replace_macros(get_markup_template('video_top.tpl'), [
				'$video' => [
					'id'     => $id,
					'title'  => L10n::t('View Video'),
					'src'    => $the_url,
					'mime'   => $mime,
				],
			]);
		}

		$filetype = strtolower(substr($mime, 0, strpos($mime, '/')));
		if ($filetype) {
			$filesubtype = strtolower(substr($mime, strpos($mime, '/') + 1));
			$filesubtype = str_replace('.', '-', $filesubtype);
		} else {
			$filetype = 'unkn';
			$filesubtype = 'unkn';
		}

		$title = escape_tags(trim(!empty($mtch[4]) ? $mtch[4] : $mtch[1]));
		$title .= ' ' . $mtch[2] . ' ' . L10n::t('bytes');

		$icon = '<div class="attachtype icon s22 type-' . $filetype . ' subtype-' . $filesubtype . '"></div>';
		$as .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="_blank" >' . $icon . '</a>';
	}

	if ($as != '') {
		$s .= '<div class="body-attach">'.$as.'<div class="clear"></div></div>';
	}

	// Map.
	if (strpos($s, '<div class="map">') !== false && x($item, 'coord')) {
		$x = Map::byCoordinates(trim($item['coord']));
		if ($x) {
			$s = preg_replace('/\<div class\=\"map\"\>/', '$0' . $x, $s);
		}
	}


	// Look for spoiler.
	$spoilersearch = '<blockquote class="spoiler">';

	// Remove line breaks before the spoiler.
	while ((strpos($s, "\n" . $spoilersearch) !== false)) {
		$s = str_replace("\n" . $spoilersearch, $spoilersearch, $s);
	}
	while ((strpos($s, "<br />" . $spoilersearch) !== false)) {
		$s = str_replace("<br />" . $spoilersearch, $spoilersearch, $s);
	}

	while ((strpos($s, $spoilersearch) !== false)) {
		$pos = strpos($s, $spoilersearch);
		$rnd = random_string(8);
		$spoilerreplace = '<br /> <span id="spoiler-wrap-' . $rnd . '" class="spoiler-wrap fakelink" onclick="openClose(\'spoiler-' . $rnd . '\');">' . L10n::t('Click to open/close') . '</span>'.
					'<blockquote class="spoiler" id="spoiler-' . $rnd . '" style="display: none;">';
		$s = substr($s, 0, $pos) . $spoilerreplace . substr($s, $pos + strlen($spoilersearch));
	}

	// Look for quote with author.
	$authorsearch = '<blockquote class="author">';

	while ((strpos($s, $authorsearch) !== false)) {
		$pos = strpos($s, $authorsearch);
		$rnd = random_string(8);
		$authorreplace = '<br /> <span id="author-wrap-' . $rnd . '" class="author-wrap fakelink" onclick="openClose(\'author-' . $rnd . '\');">' . L10n::t('Click to open/close') . '</span>'.
					'<blockquote class="author" id="author-' . $rnd . '" style="display: block;">';
		$s = substr($s, 0, $pos) . $authorreplace . substr($s, $pos + strlen($authorsearch));
	}

	// Replace friendica image url size with theme preference.
	if (x($a->theme_info, 'item_image_size')){
		$ps = $a->theme_info['item_image_size'];
		$s = preg_replace('|(<img[^>]+src="[^"]+/photo/[0-9a-f]+)-[0-9]|', "$1-" . $ps, $s);
	}

	$s = apply_content_filter($s, $filter_reasons);

	$hook_data = ['item' => $item, 'html' => $s];
	Addon::callHooks('prepare_body_final', $hook_data);

	return $hook_data['html'];
}

/**
 * Given a HTML text and a set of filtering reasons, adds a content hiding header with the provided reasons
 *
 * Reasons are expected to have been translated already.
 *
 * @param string $html
 * @param array  $reasons
 * @return string
 */
function apply_content_filter($html, array $reasons)
{
	if (count($reasons)) {
		$tpl = get_markup_template('wall/content_filter.tpl');
		$html = replace_macros($tpl, [
			'$reasons'   => $reasons,
			'$rnd'       => random_string(8),
			'$openclose' => L10n::t('Click to open/close'),
			'$html'      => $html
		]);
	}

	return $html;
}

/**
 * @brief Given a text string, convert from bbcode to html and add smilie icons.
 *
 * @param string $text String with bbcode.
 * @return string Formattet HTML.
 */
function prepare_text($text) {
	if (stristr($text, '[nosmile]')) {
		$s = BBCode::convert($text);
	} else {
		$s = Smilies::replace(BBCode::convert($text));
	}

	return trim($s);
}

/**
 * return array with details for categories and folders for an item
 *
 * @param array $item
 * @return array
 *
  * [
 *      [ // categories array
 *          {
 *               'name': 'category name',
 *               'removeurl': 'url to remove this category',
 *               'first': 'is the first in this array? true/false',
 *               'last': 'is the last in this array? true/false',
 *           } ,
 *           ....
 *       ],
 *       [ //folders array
 *			{
 *               'name': 'folder name',
 *               'removeurl': 'url to remove this folder',
 *               'first': 'is the first in this array? true/false',
 *               'last': 'is the last in this array? true/false',
 *           } ,
 *           ....
 *       ]
 *  ]
 */
function get_cats_and_terms($item)
{
	$categories = [];
	$folders = [];

	$matches = false;
	$first = true;
	$cnt = preg_match_all('/<(.*?)>/', $item['file'], $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			$categories[] = [
				'name' => xmlify(file_tag_decode($mtch[1])),
				'url' =>  "#",
				'removeurl' => ((local_user() == $item['uid'])?'filerm/' . $item['id'] . '?f=&cat=' . xmlify(file_tag_decode($mtch[1])):""),
				'first' => $first,
				'last' => false
			];
			$first = false;
		}
	}

	if (count($categories)) {
		$categories[count($categories) - 1]['last'] = true;
	}

	if (local_user() == $item['uid']) {
		$matches = false;
		$first = true;
		$cnt = preg_match_all('/\[(.*?)\]/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				$folders[] = [
					'name' => xmlify(file_tag_decode($mtch[1])),
					'url' =>  "#",
					'removeurl' => ((local_user() == $item['uid']) ? 'filerm/' . $item['id'] . '?f=&term=' . xmlify(file_tag_decode($mtch[1])) : ""),
					'first' => $first,
					'last' => false
				];
				$first = false;
			}
		}
	}

	if (count($folders)) {
		$folders[count($folders) - 1]['last'] = true;
	}

	return [$categories, $folders];
}


/**
 * get private link for item
 * @param array $item
 * @return boolean|array False if item has not plink, otherwise array('href'=>plink url, 'title'=>translated title)
 */
function get_plink($item) {
	$a = get_app();

	if ($a->user['nickname'] != "") {
		$ret = [
				//'href' => "display/" . $a->user['nickname'] . "/" . $item['id'],
				'href' => "display/" . $item['guid'],
				'orig' => "display/" . $item['guid'],
				'title' => L10n::t('View on separate page'),
				'orig_title' => L10n::t('view on separate page'),
			];

		if (x($item, 'plink')) {
			$ret["href"] = $a->remove_baseurl($item['plink']);
			$ret["title"] = L10n::t('link to source');
		}

	} elseif (x($item, 'plink') && ($item['private'] != 1)) {
		$ret = [
				'href' => $item['plink'],
				'orig' => $item['plink'],
				'title' => L10n::t('link to source'),
			];
	} else {
		$ret = [];
	}

	return $ret;
}


/**
 * replace html amp entity with amp char
 * @param string $s
 * @return string
 */
function unamp($s) {
	return str_replace('&amp;', '&', $s);
}


/**
 * return number of bytes in size (K, M, G)
 * @param string $size_str
 * @return number
 */
function return_bytes($size_str) {
	switch (substr ($size_str, -1)) {
		case 'M': case 'm': return (int)$size_str * 1048576;
		case 'K': case 'k': return (int)$size_str * 1024;
		case 'G': case 'g': return (int)$size_str * 1073741824;
		default: return $size_str;
	}
}


/**
 * @return string
 */
function generate_user_guid() {
	$found = true;
	do {
		$guid = System::createGUID(32);
		$x = q("SELECT `uid` FROM `user` WHERE `guid` = '%s' LIMIT 1",
			dbesc($guid)
		);
		if (!DBM::is_result($x)) {
			$found = false;
		}
	} while ($found == true);

	return $guid;
}


/**
 * @param string $s
 * @param boolean $strip_padding
 * @return string
 */
function base64url_encode($s, $strip_padding = false) {

	$s = strtr(base64_encode($s), '+/', '-_');

	if ($strip_padding) {
		$s = str_replace('=','',$s);
	}

	return $s;
}

/**
 * @param string $s
 * @return string
 */
function base64url_decode($s) {

	if (is_array($s)) {
		logger('base64url_decode: illegal input: ' . print_r(debug_backtrace(), true));
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

	return base64_decode(strtr($s,'-_','+/'));
}


/**
 * return div element with class 'clear'
 * @return string
 * @deprecated
 */
function cleardiv() {
	return '<div class="clear"></div>';
}


function bb_translate_video($s) {

	$matches = null;
	$r = preg_match_all("/\[video\](.*?)\[\/video\]/ism",$s,$matches,PREG_SET_ORDER);
	if ($r) {
		foreach ($matches as $mtch) {
			if ((stristr($mtch[1], 'youtube')) || (stristr($mtch[1], 'youtu.be'))) {
				$s = str_replace($mtch[0], '[youtube]' . $mtch[1] . '[/youtube]', $s);
			} elseif (stristr($mtch[1], 'vimeo')) {
				$s = str_replace($mtch[0], '[vimeo]' . $mtch[1] . '[/vimeo]', $s);
			}
		}
	}
	return $s;
}

function html2bb_video($s) {

	$s = preg_replace('#<object[^>]+>(.*?)https?://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+)(.*?)</object>#ism',
			'[youtube]$2[/youtube]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https?://www.youtube.com/embed/([A-Za-z0-9\-_=]+)(.*?)</iframe>#ism',
			'[youtube]$2[/youtube]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https?://player.vimeo.com/video/([0-9]+)(.*?)</iframe>#ism',
			'[vimeo]$2[/vimeo]', $s);

	return $s;
}

/**
 * apply xmlify() to all values of array $val, recursively
 * @param array $val
 * @return array
 */
function array_xmlify($val){
	if (is_bool($val)) {
		return $val?"true":"false";
	} elseif (is_array($val)) {
		return array_map('array_xmlify', $val);
	}
	return xmlify((string) $val);
}


/**
 * transform link href and img src from relative to absolute
 *
 * @param string $text
 * @param string $base base url
 * @return string
 */
function reltoabs($text, $base) {
	if (empty($base)) {
		return $text;
	}

	$base = rtrim($base,'/');

	$base2 = $base . "/";

	// Replace links
	$pattern = "/<a([^>]*) href=\"(?!http|https|\/)([^\"]*)\"/";
	$replace = "<a\${1} href=\"" . $base2 . "\${2}\"";
	$text = preg_replace($pattern, $replace, $text);

	$pattern = "/<a([^>]*) href=\"(?!http|https)([^\"]*)\"/";
	$replace = "<a\${1} href=\"" . $base . "\${2}\"";
	$text = preg_replace($pattern, $replace, $text);

	// Replace images
	$pattern = "/<img([^>]*) src=\"(?!http|https|\/)([^\"]*)\"/";
	$replace = "<img\${1} src=\"" . $base2 . "\${2}\"";
	$text = preg_replace($pattern, $replace, $text);

	$pattern = "/<img([^>]*) src=\"(?!http|https)([^\"]*)\"/";
	$replace = "<img\${1} src=\"" . $base . "\${2}\"";
	$text = preg_replace($pattern, $replace, $text);


	// Done
	return $text;
}

/**
 * get translated item type
 *
 * @param array $itme
 * @return string
 */
function item_post_type($item) {
	if (!empty($item['event-id'])) {
		return L10n::t('event');
	} elseif (!empty($item['resource-id'])) {
		return L10n::t('photo');
	} elseif (!empty($item['verb']) && $item['verb'] !== ACTIVITY_POST) {
		return L10n::t('activity');
	} elseif ($item['id'] != $item['parent']) {
		return L10n::t('comment');
	}

	return L10n::t('post');
}

// post categories and "save to file" use the same item.file table for storage.
// We will differentiate the different uses by wrapping categories in angle brackets
// and save to file categories in square brackets.
// To do this we need to escape these characters if they appear in our tag.

function file_tag_encode($s) {
	return str_replace(['<','>','[',']'],['%3c','%3e','%5b','%5d'],$s);
}

function file_tag_decode($s) {
	return str_replace(['%3c', '%3e', '%5b', '%5d'], ['<', '>', '[', ']'], $s);
}

function file_tag_file_query($table,$s,$type = 'file') {

	if ($type == 'file') {
		$str = preg_quote('[' . str_replace('%', '%%', file_tag_encode($s)) . ']');
	} else {
		$str = preg_quote('<' . str_replace('%', '%%', file_tag_encode($s)) . '>');
	}
	return " AND " . (($table) ? dbesc($table) . '.' : '') . "file regexp '" . dbesc($str) . "' ";
}

// ex. given music,video return <music><video> or [music][video]
function file_tag_list_to_file($list, $type = 'file') {
	$tag_list = '';
	if (strlen($list)) {
		$list_array = explode(",",$list);
		if ($type == 'file') {
			$lbracket = '[';
			$rbracket = ']';
		} else {
			$lbracket = '<';
			$rbracket = '>';
		}

		foreach ($list_array as $item) {
			if (strlen($item)) {
				$tag_list .= $lbracket . file_tag_encode(trim($item))  . $rbracket;
			}
		}
	}
	return $tag_list;
}

// ex. given <music><video>[friends], return music,video or friends
function file_tag_file_to_list($file, $type = 'file') {
	$matches = false;
	$list = '';
	if ($type == 'file') {
		$cnt = preg_match_all('/\[(.*?)\]/', $file, $matches, PREG_SET_ORDER);
	} else {
		$cnt = preg_match_all('/<(.*?)>/', $file, $matches, PREG_SET_ORDER);
	}
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (strlen($list)) {
				$list .= ',';
			}
			$list .= file_tag_decode($mtch[1]);
		}
	}

	return $list;
}

function file_tag_update_pconfig($uid, $file_old, $file_new, $type = 'file') {
	// $file_old - categories previously associated with an item
	// $file_new - new list of categories for an item

	if (!intval($uid)) {
		return false;
	} elseif ($file_old == $file_new) {
		return true;
	}

	$saved = PConfig::get($uid, 'system', 'filetags');
	if (strlen($saved)) {
		if ($type == 'file') {
			$lbracket = '[';
			$rbracket = ']';
			$termtype = TERM_FILE;
		} else {
			$lbracket = '<';
			$rbracket = '>';
			$termtype = TERM_CATEGORY;
		}

		$filetags_updated = $saved;

		// check for new tags to be added as filetags in pconfig
		$new_tags = [];
		$check_new_tags = explode(",",file_tag_file_to_list($file_new,$type));

		foreach ($check_new_tags as $tag) {
			if (!stristr($saved,$lbracket . file_tag_encode($tag) . $rbracket)) {
				$new_tags[] = $tag;
			}
		}

		$filetags_updated .= file_tag_list_to_file(implode(",",$new_tags),$type);

		// check for deleted tags to be removed from filetags in pconfig
		$deleted_tags = [];
		$check_deleted_tags = explode(",",file_tag_file_to_list($file_old,$type));

		foreach ($check_deleted_tags as $tag) {
			if (!stristr($file_new,$lbracket . file_tag_encode($tag) . $rbracket)) {
				$deleted_tags[] = $tag;
			}
		}

		foreach ($deleted_tags as $key => $tag) {
			$r = q("SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d",
				dbesc($tag),
				intval(TERM_OBJ_POST),
				intval($termtype),
				intval($uid));

			if (DBM::is_result($r)) {
				unset($deleted_tags[$key]);
			} else {
				$filetags_updated = str_replace($lbracket . file_tag_encode($tag) . $rbracket,'',$filetags_updated);
			}
		}

		if ($saved != $filetags_updated) {
			PConfig::set($uid, 'system', 'filetags', $filetags_updated);
		}
		return true;
	} elseif (strlen($file_new)) {
		PConfig::set($uid, 'system', 'filetags', $file_new);
	}
	return true;
}

function file_tag_save_file($uid, $item_id, $file)
{
	if (!intval($uid)) {
		return false;
	}

	$item = Item::selectFirst(['file'], ['id' => $item_id, 'uid' => $uid]);
	if (DBM::is_result($item)) {
		if (!stristr($item['file'],'[' . file_tag_encode($file) . ']')) {
			$fields = ['file' => $item['file'] . '[' . file_tag_encode($file) . ']'];
			Item::update($fields, ['id' => $item_id]);
		}
		$saved = PConfig::get($uid, 'system', 'filetags');
		if (!strlen($saved) || !stristr($saved, '[' . file_tag_encode($file) . ']')) {
			PConfig::set($uid, 'system', 'filetags', $saved . '[' . file_tag_encode($file) . ']');
		}
		info(L10n::t('Item filed'));
	}
	return true;
}

function file_tag_unsave_file($uid, $item_id, $file, $cat = false)
{
	if (!intval($uid)) {
		return false;
	}

	if ($cat == true) {
		$pattern = '<' . file_tag_encode($file) . '>' ;
		$termtype = TERM_CATEGORY;
	} else {
		$pattern = '[' . file_tag_encode($file) . ']' ;
		$termtype = TERM_FILE;
	}

	$item = Item::selectFirst(['file'], ['id' => $item_id, 'uid' => $uid]);
	if (!DBM::is_result($item)) {
		return false;
	}

	$fields = ['file' => str_replace($pattern,'',$item['file'])];
	Item::update($fields, ['id' => $item_id]);

	$r = q("SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d",
		dbesc($file),
		intval(TERM_OBJ_POST),
		intval($termtype),
		intval($uid)
	);
	if (!DBM::is_result($r)) {
		$saved = PConfig::get($uid, 'system', 'filetags');
		PConfig::set($uid, 'system', 'filetags', str_replace($pattern, '', $saved));
	}

	return true;
}

function normalise_openid($s) {
	return trim(str_replace(['http://', 'https://'], ['', ''], $s), '/');
}


function undo_post_tagging($s) {
	$matches = null;
	$cnt = preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $s, $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			if (in_array($mtch[1], ['!', '@'])) {
				$contact = Contact::getDetailsByURL($mtch[2]);
				$mtch[3] = empty($contact['addr']) ? $mtch[2] : $contact['addr'];
			}
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}

function protect_sprintf($s) {
	return str_replace('%', '%%', $s);
}

/// @TODO Rewrite this
function is_a_date_arg($s) {
	$i = intval($s);

	if ($i > 1900) {
		$y = date('Y');

		if ($i <= $y + 1 && strpos($s, '-') == 4) {
			$m = intval(substr($s, 5));

			if ($m > 0 && $m <= 12) {
				return true;
			}
		}
	}

	return false;
}

/**
 * remove intentation from a text
 */
function deindent($text, $chr = "[\t ]", $count = NULL) {
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

function formatBytes($bytes, $precision = 2) {
	$units = ['B', 'KB', 'MB', 'GB', 'TB'];

	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);

	$bytes /= pow(1024, $pow);

	return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * @brief translate and format the networkname of a contact
 *
 * @param string $network
 *	Networkname of the contact (e.g. dfrn, rss and so on)
 * @param sting $url
 *	The contact url
 * @return string
 */
function format_network_name($network, $url = 0) {
	if ($network != "") {
		if ($url != "") {
			$network_name = '<a href="'.$url.'">'.ContactSelector::networkToName($network, $url)."</a>";
		} else {
			$network_name = ContactSelector::networkToName($network);
		}

		return $network_name;
	}
}

/**
 * @brief Syntax based code highlighting for popular languages.
 * @param  string $text Code block content
 * @param  string $lang Programming language
 * @return string Formatted html
 */
function text_highlight($text, $lang)
{
	$highlighter = new Highlight\Highlighter();
	$r = $highlighter->highlight($lang, $text);

	return $r->value;
}
