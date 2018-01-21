<?php
/**
 * @file include/text.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Smilies;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Profile;
use Friendica\Model\Term;
use Friendica\Util\Map;

require_once "include/friendica_smarty.php";
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
				'kh', 'kl','kr','mn','pl','pr','rh','tr','qu','wh'];

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
		if ((strlen($word) > 2) && (substr($word, -2) == $noe)) {
			$word = substr($word, 0, -1);
			break;
		}
	}
	if (substr($word, -1) == 'q') {
		$word = substr($word, 0, -1);
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
		_l($data, 'prev', $url . '&page=' . ($a->pager['page'] - 1), t('newer'), 'previous' . ($a->pager['page'] == 1 ? ' disabled' : ''));
		_l($data, 'next', $url . '&page=' . ($a->pager['page'] + 1), t('older'), 'next' . ($count <= 0 ? ' disabled' : ''));
	} else {
		// full pager (first / prev / 1 / 2 / ... / 14 / 15 / next / last)
		$data['class'] = 'pagination';
		if ($a->pager['total'] > $a->pager['itemspage']) {
			_l($data, 'first', $url . '&page=1',  t('first'), $a->pager['page'] == 1 ? 'disabled' : '');
			_l($data, 'prev', $url . '&page=' . ($a->pager['page'] - 1), t('prev'), $a->pager['page'] == 1 ? 'disabled' : '');

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
			_l($data, 'next', $url . '&page=' . ($a->pager['page'] + 1), t('next'), $a->pager['page'] == $lastpage ? 'disabled' : '');
			_l($data, 'last', $url . '&page=' . $lastpage, t('last'), $a->pager['page'] == $lastpage ? 'disabled' : '');
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
		'wait' => t('Loading more entries...'),
		'end' => t('The end')
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
		$tmp = explode(',',$p);
	}

	if (is_array($tmp)) {
		array_walk($tmp, 'sanitise_acl');
		$ret = implode('', $tmp);
	}
	return $ret;
}


/**
 * generate a guaranteed unique (for this domain) item ID for ATOM
 * safe from birthday paradox
 *
 * @param string $hostname
 * @param int $uid
 * @return string
 */
function item_new_uri($hostname, $uid, $guid = "") {

	do {
		if ($guid == "") {
			$hash = get_guid(32);
		} else {
			$hash = $guid;
			$guid = "";
		}

		$uri = "urn:X-dfrn:" . $hostname . ':' . $uid . ':' . $hash;

		$dups = dba::exists('item', ['uri' => $uri]);
	} while ($dups == true);

	return $uri;
}


/**
 * Generate a guaranteed unique photo ID.
 * safe from birthday paradox
 *
 * @return string
 */
function photo_new_resource() {

	do {
		$found = false;
		$resource = hash('md5',uniqid(mt_rand(),true));
		$r = q("SELECT `id` FROM `photo` WHERE `resource-id` = '%s' LIMIT 1",
			dbesc($resource)
		);

		if (DBM::isResult($r)) {
			$found = true;
		}
	} while ($found == true);

	return $resource;
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
	if (! isset($lang)) {
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

	$theme = current_theme();

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

	if (! isset($lang)) {
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
 *
 * @param App $a
 * @param string $filename
 * @param string $root
 * @return string
 */
function get_template_file($a, $filename, $root = '') {
	$theme = current_theme();

	// Make sure $root ends with a slash /
	if ($root !== '' && substr($root, -1, 1) !== '/') {
		$root = $root . '/';
	}

	if (file_exists("{$root}view/theme/$theme/$filename")) {
		$template_file = "{$root}view/theme/$theme/$filename";
	} elseif (x($a->theme_info, "extends") && file_exists(sprintf('%sview/theme/%s}/%s', $root, $a->theme_info["extends"], $filename))) {
		$template_file = sprintf('%sview/theme/%s}/%s', $root, $a->theme_info["extends"], $filename);
	} elseif (file_exists("{$root}/$filename")) {
		$template_file = "{$root}/$filename";
	} else {
		$template_file = "{$root}view/$filename";
	}

	return $template_file;
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
		$a->module == 'install'
		|| !dba::$connected
	) {
		return;
	}

	$debugging = Config::get('system','debugging');
	$logfile   = Config::get('system','logfile');
	$loglevel = intval(Config::get('system','loglevel'));

	if (
		! $debugging
		|| ! $logfile
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
	$logline = sprintf("%s@%s\t[%s]:%s:%s:%s\t%s\n",
			datetime_convert('UTC', 'UTC', 'now', 'Y-m-d\TH:i:s\Z'),
			$process_id,
			$LOGGER_LEVELS[$level],
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
		$a->module == 'install'
		|| !dba::$connected
	) {
		return;
	}

	$logfile = Config::get('system','dlogfile');

	if (! $logfile) {
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
			datetime_convert(),
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
	if (DBM::isResult($r)) {
		$total = intval($r[0]['total']);
	}
	if (!$total) {
		$contacts = t('No contacts');
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
		if (DBM::isResult($r)) {
			$contacts = [];
			foreach ($r AS $contact) {
				$contacts[] = $contact["id"];
			}
			$r = q("SELECT `id`, `uid`, `addr`, `url`, `name`, `thumb`, `network` FROM `contact` WHERE `id` IN (%s)",
				dbesc(implode(",", $contacts)));

			if (DBM::isResult($r)) {
				$contacts = sprintf(tt('%d Contact','%d Contacts', $total),$total);
				$micropro = [];
				foreach ($r as $rr) {
					$micropro[] = micropro($rr,true,'mpfriend');
				}
			}
		}
	}

	$tpl = get_markup_template('contact_block.tpl');
	$o = replace_macros($tpl, [
		'$contacts' => $contacts,
		'$nickname' => $a->profile['nickname'],
		'$viewcontacts' => t('View Contacts'),
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
		$redirect_url = 'redir/' . $contact['id'];
		if (local_user() && ($contact['uid'] == local_user()) && ($contact['network'] === NETWORK_DFRN)) {
			$redir = true;
			$url = $redirect_url;
			$sparkle = ' sparkle';
		} else {
			$url = Profile::zrl($url);
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
	$save_label = $mode === 'text' ? t('Save') : t('Follow');

	$values = [
			'$s' => htmlspecialchars($s),
			'$id' => $id,
			'$action_url' => $url,
			'$search_label' => t('Search'),
			'$save_label' => $save_label,
			'$savedsearch' => Feature::isEnabled(local_user(),'savedsearch'),
			'$search_hint' => t('@name, !forum, #tags, content'),
			'$mode' => $mode
		];

	if (!$aside) {
		$values['$searchoption'] = [
					t("Full Text"),
					t("Tags"),
					t("Contacts")];

		if (Config::get('system','poco_local_search')) {
			$values['$searchoption'][] = t("Forums");
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
				 value is array containing past tense verb, translation of present, translation of past
 * @hook poke_verbs pokes array
 */
function get_poke_verbs() {

	// index is present tense verb
	// value is array containing past tense verb, translation of present, translation of past

	$arr = [
		'poke' => ['poked', t('poke'), t('poked')],
		'ping' => ['pinged', t('ping'), t('pinged')],
		'prod' => ['prodded', t('prod'), t('prodded')],
		'slap' => ['slapped', t('slap'), t('slapped')],
		'finger' => ['fingered', t('finger'), t('fingered')],
		'rebuff' => ['rebuffed', t('rebuff'), t('rebuffed')],
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
		[t('Monday'), t('Tuesday'), t('Wednesday'), t('Thursday'), t('Friday'), t('Saturday'), t('Sunday')],
		$s);

	$ret = str_replace(['January','February','March','April','May','June','July','August','September','October','November','December'],
		[t('January'), t('February'), t('March'), t('April'), t('May'), t('June'), t('July'), t('August'), t('September'), t('October'), t('November'), t('December')],
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
		[t('Mon'), t('Tue'), t('Wed'), t('Thu'), t('Fri'), t('Sat'), t('Sun')],
		$s);
	$ret = str_replace(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov','Dec'],
		[t('Jan'), t('Feb'), t('Mar'), t('Apr'), t('May'), ('Jun'), t('Jul'), t('Aug'), t('Sep'), t('Oct'), t('Nov'), t('Dec')],
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

function put_item_in_cache(&$item, $update = false)
{
	$rendered_hash = defaults($item, 'rendered-hash', '');

	if ($rendered_hash == ''
		|| $item["rendered-html"] == ""
		|| $rendered_hash != hash("md5", $item["body"])
		|| Config::get("system", "ignore_cache")
	) {
		// The function "redir_private_images" changes the body.
		// I'm not sure if we should store it permanently, so we save the old value.
		$body = $item["body"];

		$a = get_app();
		redir_private_images($a, $item);

		$item["rendered-html"] = prepare_text($item["body"]);
		$item["rendered-hash"] = hash("md5", $item["body"]);
		$item["body"] = $body;

		if ($update && ($item["id"] > 0)) {
			dba::update('item', ['rendered-html' => $item["rendered-html"], 'rendered-hash' => $item["rendered-hash"]],
					['id' => $item["id"]], false);
		}
	}
}

/**
 * @brief Given an item array, convert the body element from bbcode to html and add smilie icons.
 * If attach is true, also add icons for item attachments.
 *
 * @param array $item
 * @param boolean $attach
 * @return string item body html
 * @hook prepare_body_init item array before any work
 * @hook prepare_body ('item'=>item array, 'html'=>body string) after first bbcode to html
 * @hook prepare_body_final ('item'=>item array, 'html'=>body string) after attach icons and blockquote special case handling (spoiler, author)
 */
function prepare_body(&$item, $attach = false, $preview = false) {

	$a = get_app();
	Addon::callHooks('prepare_body_init', $item);

	$searchpath = System::baseUrl() . "/search?tag=";

	$tags = [];
	$hashtags = [];
	$mentions = [];

	// In order to provide theme developers more possibilities, event items
	// are treated differently.
	if ($item['object-type'] === ACTIVITY_OBJ_EVENT && isset($item['event-id'])) {
		$ev = format_event_item($item);
		return $ev;
	}

	if (!Config::get('system','suppress_tags')) {
		$taglist = dba::p("SELECT `type`, `term`, `url` FROM `term` WHERE `otype` = ? AND `oid` = ? AND `type` IN (?, ?) ORDER BY `tid`",
				intval(TERM_OBJ_POST), intval($item['id']), intval(TERM_HASHTAG), intval(TERM_MENTION));

		while ($tag = dba::fetch($taglist)) {
			if ($tag["url"] == "") {
				$tag["url"] = $searchpath.strtolower($tag["term"]);
			}

			$orig_tag = $tag["url"];

			$tag["url"] = best_link_url($item, $sp, $tag["url"]);

			if ($tag["type"] == TERM_HASHTAG) {
				if ($orig_tag != $tag["url"]) {
					$item['body'] = str_replace($orig_tag, $tag["url"], $item['body']);
				}
				$hashtags[] = "#<a href=\"".$tag["url"]."\" target=\"_blank\">".$tag["term"]."</a>";
				$prefix = "#";
			} elseif ($tag["type"] == TERM_MENTION) {
				$mentions[] = "@<a href=\"".$tag["url"]."\" target=\"_blank\">".$tag["term"]."</a>";
				$prefix = "@";
			}
			$tags[] = $prefix."<a href=\"".$tag["url"]."\" target=\"_blank\">".$tag["term"]."</a>";
		}
		dba::close($taglist);
	}

	$item['tags'] = $tags;
	$item['hashtags'] = $hashtags;
	$item['mentions'] = $mentions;

	// Update the cached values if there is no "zrl=..." on the links.
	$update = (!local_user() && !remote_user() && ($item["uid"] == 0));

	// Or update it if the current viewer is the intented viewer.
	if (($item["uid"] == local_user()) && ($item["uid"] != 0)) {
		$update = true;
	}

	put_item_in_cache($item, $update);
	$s = $item["rendered-html"];

	$prep_arr = ['item' => $item, 'html' => $s, 'preview' => $preview];
	Addon::callHooks('prepare_body', $prep_arr);
	$s = $prep_arr['html'];

	if (! $attach) {
		// Replace the blockquotes with quotes that are used in mails.
		$mailquote = '<blockquote type="cite" class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px #ccc solid;padding-left:1ex;">';
		$s = str_replace(['<blockquote>', '<blockquote class="spoiler">', '<blockquote class="author">'], [$mailquote, $mailquote, $mailquote], $s);
		return $s;
	}

	$as = '';
	$vhead = false;
	$arr = explode('[/attach],', $item['attach']);
	if (count($arr)) {
		foreach ($arr as $r) {
			$matches = false;
			$icon = '';
			$cnt = preg_match_all('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"|',$r ,$matches, PREG_SET_ORDER);
			if ($cnt) {
				foreach ($matches as $mtch) {
					$mime = $mtch[3];

					if ((local_user() == $item['uid']) && ($item['contact-id'] != $a->contact['id']) && ($item['network'] == NETWORK_DFRN)) {
						$the_url = 'redir/' . $item['contact-id'] . '?f=1&url=' . $mtch[1];
					} else {
						$the_url = $mtch[1];
					}

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

						$id = end(explode('/', $the_url));
						$as .= replace_macros(get_markup_template('video_top.tpl'), [
							'$video' => [
								'id'     => $id,
								'title'  => t('View Video'),
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

					$title = ((strlen(trim($mtch[4]))) ? escape_tags(trim($mtch[4])) : escape_tags($mtch[1]));
					$title .= ' ' . $mtch[2] . ' ' . t('bytes');

					$icon = '<div class="attachtype icon s22 type-' . $filetype . ' subtype-' . $filesubtype . '"></div>';
					$as .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="_blank" >' . $icon . '</a>';
				}
			}
		}
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
		$spoilerreplace = '<br /> <span id="spoiler-wrap-' . $rnd . '" class="spoiler-wrap fakelink" onclick="openClose(\'spoiler-' . $rnd . '\');">' . sprintf(t('Click to open/close')) . '</span>'.
					'<blockquote class="spoiler" id="spoiler-' . $rnd . '" style="display: none;">';
		$s = substr($s, 0, $pos) . $spoilerreplace . substr($s, $pos + strlen($spoilersearch));
	}

	// Look for quote with author.
	$authorsearch = '<blockquote class="author">';

	while ((strpos($s, $authorsearch) !== false)) {
		$pos = strpos($s, $authorsearch);
		$rnd = random_string(8);
		$authorreplace = '<br /> <span id="author-wrap-' . $rnd . '" class="author-wrap fakelink" onclick="openClose(\'author-' . $rnd . '\');">' . sprintf(t('Click to open/close')) . '</span>'.
					'<blockquote class="author" id="author-' . $rnd . '" style="display: block;">';
		$s = substr($s, 0, $pos) . $authorreplace . substr($s, $pos + strlen($authorsearch));
	}

	// Replace friendica image url size with theme preference.
	if (x($a->theme_info, 'item_image_size')){
		$ps = $a->theme_info['item_image_size'];
		$s = preg_replace('|(<img[^>]+src="[^"]+/photo/[0-9a-f]+)-[0-9]|', "$1-" . $ps, $s);
	}

	$prep_arr = ['item' => $item, 'html' => $s];
	Addon::callHooks('prepare_body_final', $prep_arr);

	return $prep_arr['html'];
}

/**
 * @brief Given a text string, convert from bbcode to html and add smilie icons.
 *
 * @param string $text String with bbcode.
 * @return string Formattet HTML.
 */
function prepare_text($text) {

	require_once 'include/bbcode.php';

	if (stristr($text, '[nosmile]')) {
		$s = bbcode($text);
	} else {
		$s = Smilies::replace(bbcode($text));
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
				'title' => t('View on separate page'),
				'orig_title' => t('view on separate page'),
			];

		if (x($item, 'plink')) {
			$ret["href"] = $a->remove_baseurl($item['plink']);
			$ret["title"] = t('link to source');
		}

	} elseif (x($item, 'plink') && ($item['private'] != 1)) {
		$ret = [
				'href' => $item['plink'],
				'orig' => $item['plink'],
				'title' => t('link to source'),
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
		$guid = get_guid(32);
		$x = q("SELECT `uid` FROM `user` WHERE `guid` = '%s' LIMIT 1",
			dbesc($guid)
		);
		if (! DBM::isResult($x)) {
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
 *	if (! strpos($s,'=')) {
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
			if ((stristr($mtch[1],'youtube')) || (stristr($mtch[1],'youtu.be')))
				$s = str_replace($mtch[0],'[youtube]' . $mtch[1] . '[/youtube]',$s);
			elseif (stristr($mtch[1],'vimeo'))
				$s = str_replace($mtch[0],'[vimeo]' . $mtch[1] . '[/vimeo]',$s);
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
	if (intval($item['event-id'])) {
		return t('event');
	} elseif (strlen($item['resource-id'])) {
		return t('photo');
	} elseif (strlen($item['verb']) && $item['verb'] !== ACTIVITY_POST) {
		return t('activity');
	} elseif ($item['id'] != $item['parent']) {
		return t('comment');
	}

	return t('post');
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
function file_tag_list_to_file($list,$type = 'file') {
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
function file_tag_file_to_list($file,$type = 'file') {
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
	}
	if ($file_old == $file_new) {
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
			if (! stristr($saved,$lbracket . file_tag_encode($tag) . $rbracket))
				$new_tags[] = $tag;
		}

		$filetags_updated .= file_tag_list_to_file(implode(",",$new_tags),$type);

		// check for deleted tags to be removed from filetags in pconfig
		$deleted_tags = [];
		$check_deleted_tags = explode(",",file_tag_file_to_list($file_old,$type));

		foreach ($check_deleted_tags as $tag) {
			if (! stristr($file_new,$lbracket . file_tag_encode($tag) . $rbracket))
				$deleted_tags[] = $tag;
		}

		foreach ($deleted_tags as $key => $tag) {
			$r = q("SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d",
				dbesc($tag),
				intval(TERM_OBJ_POST),
				intval($termtype),
				intval($uid));

			if (DBM::isResult($r)) {
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

function file_tag_save_file($uid, $item, $file)
{
	if (! intval($uid)) {
		return false;
	}

	$r = q("SELECT `file` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval($uid)
	);
	if (DBM::isResult($r)) {
		if (! stristr($r[0]['file'],'[' . file_tag_encode($file) . ']')) {
			q("UPDATE `item` SET `file` = '%s' WHERE `id` = %d AND `uid` = %d",
				dbesc($r[0]['file'] . '[' . file_tag_encode($file) . ']'),
				intval($item),
				intval($uid)
			);
		}

		Term::createFromItem($item);

		$saved = PConfig::get($uid, 'system', 'filetags');
		if (!strlen($saved) || !stristr($saved, '[' . file_tag_encode($file) . ']')) {
			PConfig::set($uid, 'system', 'filetags', $saved . '[' . file_tag_encode($file) . ']');
		}
		info(t('Item filed'));
	}
	return true;
}

function file_tag_unsave_file($uid, $item, $file, $cat = false)
{
	if (! intval($uid)) {
		return false;
	}

	if ($cat == true) {
		$pattern = '<' . file_tag_encode($file) . '>' ;
		$termtype = TERM_CATEGORY;
	} else {
		$pattern = '[' . file_tag_encode($file) . ']' ;
		$termtype = TERM_FILE;
	}

	$r = q("SELECT `file` FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval($uid)
	);
	if (! DBM::isResult($r)) {
		return false;
	}

	q("UPDATE `item` SET `file` = '%s' WHERE `id` = %d AND `uid` = %d",
		dbesc(str_replace($pattern,'',$r[0]['file'])),
		intval($item),
		intval($uid)
	);

	Term::createFromItem($item);

	$r = q("SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d",
		dbesc($file),
		intval(TERM_OBJ_POST),
		intval($termtype),
		intval($uid)
	);
	if (!DBM::isResult($r)) {
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
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}

function protect_sprintf($s) {
	return str_replace('%', '%%', $s);
}


function is_a_date_arg($s) {
	$i = intval($s);
	if ($i > 1900) {
		$y = date('Y');
		if ($i <= $y + 1 && strpos($s, '-') == 4) {
			$m = intval(substr($s,5));
			if ($m > 0 && $m <= 12)
				return true;
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
 * @param string $s Code block
 * @param string $lang Programming language
 * @return string Formated html
 */
function text_highlight($s, $lang) {
	if ($lang === 'js') {
		$lang = 'javascript';
	}

	// @TODO: Replace Text_Highlighter_Renderer_Html by scrivo/highlight.php

	// Autoload the library to make constants available
	class_exists('Text_Highlighter_Renderer_Html');

	$options = [
		'numbers' => HL_NUMBERS_LI,
		'tabsize' => 4,
	];

	$tag_added = false;
	$s = trim(html_entity_decode($s, ENT_COMPAT));
	$s = str_replace('    ', "\t", $s);

	/*
	 * The highlighter library insists on an opening php tag for php code blocks. If
	 * it isn't present, nothing is highlighted. So we're going to see if it's present.
	 * If not, we'll add it, and then quietly remove it after we get the processed output back.
	 */
	if ($lang === 'php' && strpos($s, '<?php') !== 0) {
		$s = '<?php' . "\n" . $s;
		$tag_added = true;
	}

	$renderer = new Text_Highlighter_Renderer_Html($options);
	$factory = new Text_Highlighter();
	$hl = $factory->factory($lang);
	$hl->setRenderer($renderer);
	$o = $hl->highlight($s);
	$o = str_replace("\n", '', $o);

	if ($tag_added) {
		$b = substr($o, 0, strpos($o, '<li>'));
		$e = substr($o, strpos($o, '</li>'));
		$o = $b . $e;
	}

	return '<code>' . $o . '</code>';
}
