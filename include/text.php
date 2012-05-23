<?php

// This is our template processor.
// $s is the string requiring macro substitution.
// $r is an array of key value pairs (search => replace)
// returns substituted string.
// WARNING: this is pretty basic, and doesn't properly handle search strings that are substrings of each other.
// For instance if 'test' => "foo" and 'testing' => "bar", testing could become either bar or fooing, 
// depending on the order in which they were declared in the array.   

require_once("include/template_processor.php");

if(! function_exists('replace_macros')) {  
function replace_macros($s,$r) {
	global $t;
	
	//$ts = microtime();
	$r =  $t->replace($s,$r);
	//$tt = microtime() - $ts;
	
	//$a = get_app();
	//$a->page['debug'] .= "$tt <br>\n";
	return template_unescape($r);

}}


// random string, there are 86 characters max in text mode, 128 for hex
// output is urlsafe

define('RANDOM_STRING_HEX',  0x00 );
define('RANDOM_STRING_TEXT', 0x01 );

if(! function_exists('random_string')) {
function random_string($size = 64,$type = RANDOM_STRING_HEX) {
	// generate a bit of entropy and run it through the whirlpool
	$s = hash('whirlpool', (string) rand() . uniqid(rand(),true) . (string) rand(),(($type == RANDOM_STRING_TEXT) ? true : false));
	$s = (($type == RANDOM_STRING_TEXT) ? str_replace("\n","",base64url_encode($s,true)) : $s);
	return(substr($s,0,$size));
}}

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
 */

if(! function_exists('notags')) {
function notags($string) {

	return(str_replace(array("<",">"), array('[',']'), $string));

//  High-bit filter no longer used
//	return(str_replace(array("<",">","\xBA","\xBC","\xBE"), array('[',']','','',''), $string));
}}

// use this on "body" or "content" input where angle chars shouldn't be removed,
// and allow them to be safely displayed.

if(! function_exists('escape_tags')) {
function escape_tags($string) {

	return(htmlspecialchars($string));
}}


// generate a string that's random, but usually pronounceable. 
// used to generate initial passwords

if(! function_exists('autoname')) {
function autoname($len) {

	if($len <= 0)
		return '';

	$vowels = array('a','a','ai','au','e','e','e','ee','ea','i','ie','o','ou','u'); 
	if(mt_rand(0,5) == 4)
		$vowels[] = 'y';

	$cons = array(
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
			);

	$midcons = array('ck','ct','gn','ld','lf','lm','lt','mb','mm', 'mn','mp',
				'nd','ng','nk','nt','rn','rp','rt');

	$noend = array('bl', 'br', 'cl','cr','dr','fl','fr','gl','gr',
				'kh', 'kl','kr','mn','pl','pr','rh','tr','qu','wh');

	$start = mt_rand(0,2);
  	if($start == 0)
    		$table = $vowels;
  	else
    		$table = $cons;

	$word = '';

	for ($x = 0; $x < $len; $x ++) {
  		$r = mt_rand(0,count($table) - 1);
  		$word .= $table[$r];
  
  		if($table == $vowels)
    			$table = array_merge($cons,$midcons);
  		else
    			$table = $vowels;

	}

	$word = substr($word,0,$len);

	foreach($noend as $noe) {
  		if((strlen($word) > 2) && (substr($word,-2) == $noe)) {
    			$word = substr($word,0,-1);
    			break;
  		}
	}
	if(substr($word,-1) == 'q')
		$word = substr($word,0,-1);    
	return $word;
}}


// escape text ($str) for XML transport
// returns escaped text.

if(! function_exists('xmlify')) {
function xmlify($str) {
	$buffer = '';
	
	for($x = 0; $x < mb_strlen($str); $x ++) {
		$char = $str[$x];
        
		switch( $char ) {

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
	}
	$buffer = trim($buffer);
	return($buffer);
}}

// undo an xmlify
// pass xml escaped text ($s), returns unescaped text

if(! function_exists('unxmlify')) {
function unxmlify($s) {
	$ret = str_replace('&amp;','&', $s);
	$ret = str_replace(array('&lt;','&gt;','&quot;','&apos;'),array('<','>','"',"'"),$ret);
	return $ret;	
}}

// convenience wrapper, reverse the operation "bin2hex"

if(! function_exists('hex2bin')) {
function hex2bin($s) {
	if(! (is_string($s) && strlen($s)))
		return '';

	if(! ctype_xdigit($s)) {
		return($s);
	}

	return(pack("H*",$s));
}}

// Automatic pagination.
// To use, get the count of total items.
// Then call $a->set_pager_total($number_items);
// Optionally call $a->set_pager_itemspage($n) to the number of items to display on each page
// Then call paginate($a) after the end of the display loop to insert the pager block on the page
// (assuming there are enough items to paginate).
// When using with SQL, the setting LIMIT %d, %d => $a->pager['start'],$a->pager['itemspage']
// will limit the results to the correct items for the current page. 
// The actual page handling is then accomplished at the application layer. 

if(! function_exists('paginate')) {
function paginate(&$a) {
	$o = '';
	$stripped = preg_replace('/(&page=[0-9]*)/','',$a->query_string);

//	$stripped = preg_replace('/&zrl=(.*?)([\?&]|$)/ism','',$stripped);

	$stripped = str_replace('q=','',$stripped);
	$stripped = trim($stripped,'/');
	$pagenum = $a->pager['page'];
	$url = $a->get_baseurl() . '/' . $stripped;


	  if($a->pager['total'] > $a->pager['itemspage']) {
		$o .= '<div class="pager">';
    		if($a->pager['page'] != 1)
			$o .= '<span class="pager_prev">'."<a href=\"$url".'&page='.($a->pager['page'] - 1).'">' . t('prev') . '</a></span> ';

		$o .=  "<span class=\"pager_first\"><a href=\"$url"."&page=1\">" . t('first') . "</a></span> ";

    		$numpages = $a->pager['total'] / $a->pager['itemspage'];

			$numstart = 1;
    		$numstop = $numpages;

    		if($numpages > 14) {
      			$numstart = (($pagenum > 7) ? ($pagenum - 7) : 1);
      			$numstop = (($pagenum > ($numpages - 7)) ? $numpages : ($numstart + 14));
    		}
   
		for($i = $numstart; $i <= $numstop; $i++){
      			if($i == $a->pager['page'])
				$o .= '<span class="pager_current">'.(($i < 10) ? '&nbsp;'.$i : $i);
			else
				$o .= "<span class=\"pager_n\"><a href=\"$url"."&page=$i\">".(($i < 10) ? '&nbsp;'.$i : $i)."</a>";
			$o .= '</span> ';
		}

		if(($a->pager['total'] % $a->pager['itemspage']) != 0) {
			if($i == $a->pager['page'])
				$o .= '<span class="pager_current">'.(($i < 10) ? '&nbsp;'.$i : $i);
			else
				$o .= "<span class=\"pager_n\"><a href=\"$url"."&page=$i\">".(($i < 10) ? '&nbsp;'.$i : $i)."</a>";
			$o .= '</span> ';
		}

		$lastpage = (($numpages > intval($numpages)) ? intval($numpages)+1 : $numpages);
		$o .= "<span class=\"pager_last\"><a href=\"$url"."&page=$lastpage\">" . t('last') . "</a></span> ";

    		if(($a->pager['total'] - ($a->pager['itemspage'] * $a->pager['page'])) > 0)
			$o .= '<span class="pager_next">'."<a href=\"$url"."&page=".($a->pager['page'] + 1).'">' . t('next') . '</a></span>';
		$o .= '</div>'."\r\n";
	}
	return $o;
}}

// Turn user/group ACLs stored as angle bracketed text into arrays

if(! function_exists('expand_acl')) {
function expand_acl($s) {
	// turn string array of angle-bracketed elements into numeric array
	// e.g. "<1><2><3>" => array(1,2,3);
	$ret = array();

	if(strlen($s)) {
		$t = str_replace('<','',$s);
		$a = explode('>',$t);
		foreach($a as $aa) {
			if(intval($aa))
				$ret[] = intval($aa);
		}
	}
	return $ret;
}}		

// Used to wrap ACL elements in angle brackets for storage 

if(! function_exists('sanitise_acl')) {
function sanitise_acl(&$item) {
	if(intval($item))
		$item = '<' . intval(notags(trim($item))) . '>';
	else
		unset($item);
}}


// Convert an ACL array to a storable string

if(! function_exists('perms2str')) {
function perms2str($p) {
	$ret = '';
	$tmp = $p;
	if(is_array($tmp)) {
		array_walk($tmp,'sanitise_acl');
		$ret = implode('',$tmp);
	}
	return $ret;
}}

// generate a guaranteed unique (for this domain) item ID for ATOM
// safe from birthday paradox

if(! function_exists('item_new_uri')) {
function item_new_uri($hostname,$uid) {

	do {
		$dups = false;
		$hash = random_string();

		$uri = "urn:X-dfrn:" . $hostname . ':' . $uid . ':' . $hash;

		$r = q("SELECT `id` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($uri));
		if(count($r))
			$dups = true;
	} while($dups == true);
	return $uri;
}}

// Generate a guaranteed unique photo ID.
// safe from birthday paradox

if(! function_exists('photo_new_resource')) {
function photo_new_resource() {

	do {
		$found = false;
		$resource = hash('md5',uniqid(mt_rand(),true));
		$r = q("SELECT `id` FROM `photo` WHERE `resource-id` = '%s' LIMIT 1",
			dbesc($resource)
		);
		if(count($r))
			$found = true;
	} while($found == true);
	return $resource;
}}


// wrapper to load a view template, checking for alternate
// languages before falling back to the default

// obsolete, deprecated.

if(! function_exists('load_view_file')) {
function load_view_file($s) {
	global $lang, $a;
	if(! isset($lang))
		$lang = 'en';
	$b = basename($s);
	$d = dirname($s);
	if(file_exists("$d/$lang/$b"))
		return file_get_contents("$d/$lang/$b");
	
	$theme = current_theme();
	
	if(file_exists("$d/theme/$theme/$b"))
		return file_get_contents("$d/theme/$theme/$b");
			
	return file_get_contents($s);
}}

if(! function_exists('get_intltext_template')) {
function get_intltext_template($s) {
	global $lang;

	if(! isset($lang))
		$lang = 'en';

	if(file_exists("view/$lang/$s"))
		return file_get_contents("view/$lang/$s");
	elseif(file_exists("view/en/$s"))
		return file_get_contents("view/en/$s");
	else
		return file_get_contents("view/$s");
}}

if(! function_exists('get_markup_template')) {
function get_markup_template($s) {
	$a=get_app();
	$theme = current_theme();
	
	if(file_exists("view/theme/$theme/$s"))
		return file_get_contents("view/theme/$theme/$s");
	elseif (x($a->theme_info,"extends") && file_exists("view/theme/".$a->theme_info["extends"]."/$s"))
		return file_get_contents("view/theme/".$a->theme_info["extends"]."/$s");
	else
		return file_get_contents("view/$s");

}}





// for html,xml parsing - let's say you've got
// an attribute foobar="class1 class2 class3"
// and you want to find out if it contains 'class3'.
// you can't use a normal sub string search because you
// might match 'notclass3' and a regex to do the job is 
// possible but a bit complicated. 
// pass the attribute string as $attr and the attribute you 
// are looking for as $s - returns true if found, otherwise false

if(! function_exists('attribute_contains')) {
function attribute_contains($attr,$s) {
	$a = explode(' ', $attr);
	if(count($a) && in_array($s,$a))
		return true;
	return false;
}}

if(! function_exists('logger')) {
function logger($msg,$level = 0) {
	// turn off logger in install mode
	global $a;
	global $db;

	if(($a->module == 'install') || (! ($db && $db->connected))) return;

	$debugging = get_config('system','debugging');
	$loglevel  = intval(get_config('system','loglevel'));
	$logfile   = get_config('system','logfile');

	if((! $debugging) || (! $logfile) || ($level > $loglevel))
		return;
	
	@file_put_contents($logfile, datetime_convert() . ':' . session_id() . ' ' . $msg . "\n", FILE_APPEND);
	return;
}}


if(! function_exists('activity_match')) {
function activity_match($haystack,$needle) {
	if(($haystack === $needle) || ((basename($needle) === $haystack) && strstr($needle,NAMESPACE_ACTIVITY_SCHEMA)))
		return true;
	return false;
}}


// Pull out all #hashtags and @person tags from $s;
// We also get @person@domain.com - which would make 
// the regex quite complicated as tags can also
// end a sentence. So we'll run through our results
// and strip the period from any tags which end with one.
// Returns array of tags found, or empty array.


if(! function_exists('get_tags')) {
function get_tags($s) {
	$ret = array();

	// ignore anything in a code block

	$s = preg_replace('/\[code\](.*?)\[\/code\]/sm','',$s);

	// Match full names against @tags including the space between first and last
	// We will look these up afterward to see if they are full names or not recognisable.

	if(preg_match_all('/(@[^ \x0D\x0A,:?]+ [^ \x0D\x0A@,:?]+)([ \x0D\x0A@,:?]|$)/',$s,$match)) {
		foreach($match[1] as $mtch) {
			if(strstr($mtch,"]")) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if(substr($mtch,-1,1) === '.')
				$ret[] = substr($mtch,0,-1);
			else
				$ret[] = $mtch;
		}
	}

	// Otherwise pull out single word tags. These can be @nickname, @first_last
	// and #hash tags.

	if(preg_match_all('/([@#][^ \x0D\x0A,;:?]+)([ \x0D\x0A,;:?]|$)/',$s,$match)) {
		foreach($match[1] as $mtch) {
			if(strstr($mtch,"]")) {
				// we might be inside a bbcode color tag - leave it alone
				continue;
			}
			if(substr($mtch,-1,1) === '.')
				$mtch = substr($mtch,0,-1);
			// ignore strictly numeric tags like #1
			if((strpos($mtch,'#') === 0) && ctype_digit(substr($mtch,1)))
				continue;
			// try not to catch url fragments
			if(strpos($s,$mtch) && preg_match('/[a-zA-z0-9\/]/',substr($s,strpos($s,$mtch)-1,1)))
				continue;
			$ret[] = $mtch;
		}
	}
	return $ret;
}}


// quick and dirty quoted_printable encoding

if(! function_exists('qp')) {
function qp($s) {
return str_replace ("%","=",rawurlencode($s));
}} 



if(! function_exists('get_mentions')) {
function get_mentions($item) {
	$o = '';
	if(! strlen($item['tag']))
		return $o;

	$arr = explode(',',$item['tag']);
	foreach($arr as $x) {
		$matches = null;
		if(preg_match('/@\[url=([^\]]*)\]/',$x,$matches)) {
			$o .= "\t\t" . '<link rel="mentioned" href="' . $matches[1] . '" />' . "\r\n";
			$o .= "\t\t" . '<link rel="ostatus:attention" href="' . $matches[1] . '" />' . "\r\n";
		}
	}
	return $o;
}}

if(! function_exists('contact_block')) {
function contact_block() {
	$o = '';
	$a = get_app();

	$shown = get_pconfig($a->profile['uid'],'system','display_friend_count');
	if($shown === false)
		$shown = 24;
	if($shown == 0)
		return;

	if((! is_array($a->profile)) || ($a->profile['hide-friends']))
		return $o;
	$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 AND `hidden` = 0 AND `archive` = 0",
			intval($a->profile['uid'])
	);
	if(count($r)) {
		$total = intval($r[0]['total']);
	}
	if(! $total) {
		$contacts = t('No contacts');
		$micropro = Null;
		
	} else {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 AND `hidden` = 0 AND `archive` = 0 ORDER BY RAND() LIMIT %d",
				intval($a->profile['uid']),
				intval($shown)
		);
		if(count($r)) {
			$contacts = sprintf( tt('%d Contact','%d Contacts', $total),$total);
			$micropro = Array();
			foreach($r as $rr) {
				$micropro[] = micropro($rr,true,'mpfriend');
			}
		}
	}
	
	$tpl = get_markup_template('contact_block.tpl');
	$o = replace_macros($tpl, array(
		'$contacts' => $contacts,
		'$nickname' => $a->profile['nickname'],
		'$viewcontacts' => t('View Contacts'),
		'$micropro' => $micropro,
	));

	$arr = array('contacts' => $r, 'output' => $o);

	call_hooks('contact_block_end', $arr);
	return $o;

}}

if(! function_exists('micropro')) {
function micropro($contact, $redirect = false, $class = '', $textmode = false) {

	if($class)
		$class = ' ' . $class;

	$url = $contact['url'];
	$sparkle = '';
	$redir = false;

	if($redirect) {
		$a = get_app();
		$redirect_url = $a->get_baseurl() . '/redir/' . $contact['id'];
		if(local_user() && ($contact['uid'] == local_user()) && ($contact['network'] === 'dfrn')) {
			$redir = true;
			$url = $redirect_url;
			$sparkle = ' sparkle';
		}
		else
			$url = zrl($url);
	}
	$click = ((x($contact,'click')) ? ' onclick="' . $contact['click'] . '" ' : '');
	if($click)
		$url = '';
	if($textmode) {
		return '<div class="contact-block-textdiv' . $class . '"><a class="contact-block-link' . $class . $sparkle 
			. (($click) ? ' fakelink' : '') . '" '
			. (($redir) ? ' target="redir" ' : '')
			. (($url) ? ' href="' . $url . '"' : '') . $click
			. '" title="' . $contact['name'] . ' [' . $contact['url'] . ']" alt="' . $contact['name'] 
			. '" >'. $contact['name'] . '</a></div>' . "\r\n";
	}
	else {
		return '<div class="contact-block-div' . $class . '"><a class="contact-block-link' . $class . $sparkle 
			. (($click) ? ' fakelink' : '') . '" '
			. (($redir) ? ' target="redir" ' : '')
			. (($url) ? ' href="' . $url . '"' : '') . $click . ' ><img class="contact-block-img' . $class . $sparkle . '" src="' 
			. $contact['micro'] . '" title="' . $contact['name'] . ' [' . $contact['url'] . ']" alt="' . $contact['name'] 
			. '" /></a></div>' . "\r\n";
	}
}}



if(! function_exists('search')) {
function search($s,$id='search-box',$url='/search',$save = false) {
	$a = get_app();
	$o  = '<div id="' . $id . '">';
	$o .= '<form action="' . $a->get_baseurl((stristr($url,'network')) ? true : false) . $url . '" method="get" >';
	$o .= '<input type="text" name="search" id="search-text" placeholder="' . t('Search') . '" value="' . $s .'" />';
	$o .= '<input type="submit" name="submit" id="search-submit" value="' . t('Search') . '" />'; 
	if($save)
		$o .= '<input type="submit" name="save" id="search-save" value="' . t('Save') . '" />'; 
	$o .= '</form></div>';
	return $o;
}}

if(! function_exists('valid_email')) {
function valid_email($x){
	if(preg_match('/^[_a-zA-Z0-9\-\+]+(\.[_a-zA-Z0-9\-\+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',$x))
		return true;
	return false;
}}


/**
 *
 * Function: linkify
 *
 * Replace naked text hyperlink with HTML formatted hyperlink
 *
 */

if(! function_exists('linkify')) {
function linkify($s) {
	$s = preg_replace("/(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\'\%\$\!\+]*)/", ' <a href="$1" target="external-link">$1</a>', $s);
	$s = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$s);
	return($s);
}}


/**
 * 
 * Function: smilies
 *
 * Description:
 * Replaces text emoticons with graphical images
 *
 * @Parameter: string $s
 *
 * Returns string
 *
 * It is expected that this function will be called using HTML text.
 * We will escape text between HTML pre and code blocks from being 
 * processed. 
 * 
 * At a higher level, the bbcode [nosmile] tag can be used to prevent this 
 * function from being executed by the prepare_text() routine when preparing
 * bbcode source for HTML display
 *
 */

if(! function_exists('smilies')) {
function smilies($s, $sample = false) {

	$a = get_app();

	if(intval(get_config('system','no_smilies')) 
		|| (local_user() && intval(get_pconfig(local_user(),'system','no_smilies'))))
		return $s;

	$s = preg_replace_callback('/<pre>(.*?)<\/pre>/ism','smile_encode',$s);
	$s = preg_replace_callback('/<code>(.*?)<\/code>/ism','smile_encode',$s);

	$texts =  array( 
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
		'~friendika', 
		'~friendica'

	);

	$icons = array(
		'<img src="' . $a->get_baseurl() . '/images/smiley-heart.gif" alt="<3" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-brokenheart.gif" alt="</3" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-brokenheart.gif" alt="<\\3" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-smile.gif" alt=":-)" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-wink.gif" alt=";-)" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-frown.gif" alt=":-(" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-tongue-out.gif" alt=":-P" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-tongue-out.gif" alt=":-p" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-\"" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-\"" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-x" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-kiss.gif" alt=":-X" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-laughing.gif" alt=":-D" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt="8-|" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt="8-O" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-surprised.gif" alt=":-O" />',                
		'<img src="' . $a->get_baseurl() . '/images/smiley-thumbsup.gif" alt="\\o/" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="o.O" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="O.o" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="o_O" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-Oo.gif" alt="O_o" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-cry.gif" alt=":\'(" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-foot-in-mouth.gif" alt=":-!" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-undecided.gif" alt=":-/" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-embarassed.gif" alt=":-[" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-cool.gif" alt="8-)" />',
		'<img src="' . $a->get_baseurl() . '/images/beer_mug.gif" alt=":beer" />',
		'<img src="' . $a->get_baseurl() . '/images/beer_mug.gif" alt=":homebrew" />',
		'<img src="' . $a->get_baseurl() . '/images/coffee.gif" alt=":coffee" />',
		'<img src="' . $a->get_baseurl() . '/images/smiley-facepalm.gif" alt=":facepalm" />',
		'<a href="http://project.friendika.com">~friendika <img src="' . $a->get_baseurl() . '/images/friendika-16.png" alt="~friendika" /></a>',
		'<a href="http://friendica.com">~friendica <img src="' . $a->get_baseurl() . '/images/friendica-16.png" alt="~friendica" /></a>'
	);

	$params = array('texts' => $texts, 'icons' => $icons, 'string' => $s);
	call_hooks('smilie', $params);

	if($sample) {
		$s = '<div class="smiley-sample">';
		for($x = 0; $x < count($params['texts']); $x ++) {
			$s .= '<dl><dt>' . $params['texts'][$x] . '</dt><dd>' . $params['icons'][$x] . '</dd></dl>';
		}
	}
	else {
		$params['string'] = preg_replace_callback('/&lt;(3+)/','preg_heart',$params['string']);
		$s = str_replace($params['texts'],$params['icons'],$params['string']);
	}

	$s = preg_replace_callback('/<pre>(.*?)<\/pre>/ism','smile_decode',$s);
	$s = preg_replace_callback('/<code>(.*?)<\/code>/ism','smile_decode',$s);

	return $s;

}}

function smile_encode($m) {
	return(str_replace($m[1],base64url_encode($m[1]),$m[0]));
}

function smile_decode($m) {
	return(str_replace($m[1],base64url_decode($m[1]),$m[0]));
}

// expand <3333 to the correct number of hearts

function preg_heart($x) {
	$a = get_app();
	if(strlen($x[1]) == 1)
		return $x[0];
	$t = '';
	for($cnt = 0; $cnt < strlen($x[1]); $cnt ++)
		$t .= '<img src="' . $a->get_baseurl() . '/images/smiley-heart.gif" alt="<3" />';
	$r =  str_replace($x[0],$t,$x[0]);
	return $r;
}


if(! function_exists('day_translate')) {
function day_translate($s) {
	$ret = str_replace(array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
		array( t('Monday'), t('Tuesday'), t('Wednesday'), t('Thursday'), t('Friday'), t('Saturday'), t('Sunday')),
		$s);

	$ret = str_replace(array('January','February','March','April','May','June','July','August','September','October','November','December'),
		array( t('January'), t('February'), t('March'), t('April'), t('May'), t('June'), t('July'), t('August'), t('September'), t('October'), t('November'), t('December')),
		$ret);

	return $ret;
}}


if(! function_exists('normalise_link')) {
function normalise_link($url) {
	$ret = str_replace(array('https:','//www.'), array('http:','//'), $url);
	return(rtrim($ret,'/'));
}}

/**
 *
 * Compare two URLs to see if they are the same, but ignore
 * slight but hopefully insignificant differences such as if one 
 * is https and the other isn't, or if one is www.something and 
 * the other isn't - and also ignore case differences.
 *
 * Return true if the URLs match, otherwise false.
 *
 */

if(! function_exists('link_compare')) {
function link_compare($a,$b) {
	if(strcasecmp(normalise_link($a),normalise_link($b)) === 0)
		return true;
	return false;
}}

// Given an item array, convert the body element from bbcode to html and add smilie icons.
// If attach is true, also add icons for item attachments


if(! function_exists('prepare_body')) {
function prepare_body($item,$attach = false) {

	$a = get_app();
	call_hooks('prepare_body_init', $item); 

	$cache = get_config('system','itemcache');

	if (($cache != '')) {
		$cachefile = $cache."/".$item["guid"]."-".strtotime($item["edited"])."-".hash("crc32", $item['body']);

		if (file_exists($cachefile))
			$s = file_get_contents($cachefile);
		else {
			$s = prepare_text($item['body']);
			file_put_contents($cachefile, $s);
		}
	} else
		$s = prepare_text($item['body']);

	$prep_arr = array('item' => $item, 'html' => $s);
	call_hooks('prepare_body', $prep_arr);
	$s = $prep_arr['html'];

	if(! $attach) {
		return $s;
	}

	$arr = explode(',',$item['attach']);
	if(count($arr)) {
		$s .= '<div class="body-attach">';
		foreach($arr as $r) {
			$matches = false;
			$icon = '';
			$cnt = preg_match_all('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"\[\/attach\]|',$r,$matches, PREG_SET_ORDER);
			if($cnt) {
				foreach($matches as $mtch) {
					$icontype = strtolower(substr($mtch[3],0,strpos($mtch[3],'/')));
					switch($icontype) {
						case 'video':
						case 'audio':
						case 'image':
						case 'text':
							$icon = '<div class="attachtype icon s22 type-' . $icontype . '"></div>';
							break;
						default:
							$icon = '<div class="attachtype icon s22 type-unkn"></div>';
							break;
					}
					$title = ((strlen(trim($mtch[4]))) ? escape_tags(trim($mtch[4])) : escape_tags($mtch[1]));
					$title .= ' ' . $mtch[2] . ' ' . t('bytes');
					if((local_user() == $item['uid']) && $item['contact-id'] != $a->contact['id'])
						$the_url = $a->get_baseurl() . '/redir/' . $item['contact-id'] . '?f=1&url=' . $mtch[1];
					else
						$the_url = $mtch[1];

					$s .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="external-link" >' . $icon . '</a>';
				}
			}
		}
		$s .= '<div class="clear"></div></div>';
	}
	$matches = false;
	$cnt = preg_match_all('/<(.*?)>/',$item['file'],$matches,PREG_SET_ORDER);
	if($cnt) {
//		logger('prepare_text: categories: ' . print_r($matches,true), LOGGER_DEBUG);
		foreach($matches as $mtch) {
			if(strlen($x))
				$x .= ',';
			$x .= xmlify(file_tag_decode($mtch[1])) 
				. ((local_user() == $item['uid']) ? ' <a href="' . $a->get_baseurl() . '/filerm/' . $item['id'] . '?f=&cat=' . xmlify(file_tag_decode($mtch[1])) . '" title="' . t('remove') . '" >' . t('[remove]') . '</a>' : '');
		}
		if(strlen($x))
			$s .= '<div class="categorytags"><span>' . t('Categories:') . ' </span>' . $x . '</div>'; 


	}
	$matches = false;
	$x = '';
	$cnt = preg_match_all('/\[(.*?)\]/',$item['file'],$matches,PREG_SET_ORDER);
	if($cnt) {
//		logger('prepare_text: filed_under: ' . print_r($matches,true), LOGGER_DEBUG);
		foreach($matches as $mtch) {
			if(strlen($x))
				$x .= '&nbsp;&nbsp;&nbsp;';
			$x .= xmlify(file_tag_decode($mtch[1])) . ' <a href="' . $a->get_baseurl() . '/filerm/' . $item['id'] . '?f=&term=' . xmlify(file_tag_decode($mtch[1])) . '" title="' . t('remove') . '" >' . t('[remove]') . '</a>';
		}
		if(strlen($x) && (local_user() == $item['uid']))
			$s .= '<div class="filesavetags"><span>' . t('Filed under:') . ' </span>' . $x . '</div>'; 
	}

	// Look for spoiler
	$spoilersearch = '<blockquote class="spoiler">';

	// Remove line breaks before the spoiler
	while ((strpos($s, "\n".$spoilersearch) !== false))
		$s = str_replace("\n".$spoilersearch, $spoilersearch, $s);
	while ((strpos($s, "<br />".$spoilersearch) !== false))
		$s = str_replace("<br />".$spoilersearch, $spoilersearch, $s);

	while ((strpos($s, $spoilersearch) !== false)) {

		$pos = strpos($s, $spoilersearch);
		$rnd = random_string(8);
		$spoilerreplace = '<br /> <span id="spoiler-wrap-'.$rnd.'" style="white-space:nowrap;" class="fakelink" onclick="openClose(\'spoiler-'.$rnd.'\');">'.sprintf(t('Click to open/close')).'</span>'.
	                                '<blockquote class="spoiler" id="spoiler-'.$rnd.'" style="display: none;">';
		$s = substr($s, 0, $pos).$spoilerreplace.substr($s, $pos+strlen($spoilersearch));
	}

	// Look for quote with author
	$authorsearch = '<blockquote class="author">';

	while ((strpos($s, $authorsearch) !== false)) {

		$pos = strpos($s, $authorsearch);
		$rnd = random_string(8);
		$authorreplace = '<br /> <span id="author-wrap-'.$rnd.'" style="white-space:nowrap;" class="fakelink" onclick="openClose(\'author-'.$rnd.'\');">'.sprintf(t('Click to open/close')).'</span>'.
	                                '<blockquote class="author" id="author-'.$rnd.'" style="display: block;">';
		$s = substr($s, 0, $pos).$authorreplace.substr($s, $pos+strlen($authorsearch));
	}

	$prep_arr = array('item' => $item, 'html' => $s);
	call_hooks('prepare_body_final', $prep_arr);

	return $prep_arr['html'];
}}


// Given a text string, convert from bbcode to html and add smilie icons.

if(! function_exists('prepare_text')) {
function prepare_text($text) {

	require_once('include/bbcode.php');

	if(stristr($text,'[nosmile]'))
		$s = bbcode($text);
	else
		$s = smilies(bbcode($text));

	return $s;
}}


/**
 * return atom link elements for all of our hubs
 */

if(! function_exists('feed_hublinks')) {
function feed_hublinks() {

	$hub = get_config('system','huburl');

	$hubxml = '';
	if(strlen($hub)) {
		$hubs = explode(',', $hub);
		if(count($hubs)) {
			foreach($hubs as $h) {
				$h = trim($h);
				if(! strlen($h))
					continue;
				$hubxml .= '<link rel="hub" href="' . xmlify($h) . '" />' . "\n" ;
			}
		}
	}
	return $hubxml;
}}

/* return atom link elements for salmon endpoints */

if(! function_exists('feed_salmonlinks')) {
function feed_salmonlinks($nick) {

	$a = get_app();

	$salmon  = '<link rel="salmon" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ;

	// old style links that status.net still needed as of 12/2010 

	$salmon .= '  <link rel="http://salmon-protocol.org/ns/salmon-replies" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ; 
	$salmon .= '  <link rel="http://salmon-protocol.org/ns/salmon-mention" href="' . xmlify($a->get_baseurl() . '/salmon/' . $nick) . '" />' . "\n" ; 
	return $salmon;
}}

if(! function_exists('get_plink')) {
function get_plink($item) {
	$a = get_app();	
	if (x($item,'plink') && (! $item['private'])){
		return array(
			'href' => $item['plink'],
			'title' => t('link to source'),
		);
	} else {
		return false;
	}
}}

if(! function_exists('unamp')) {
function unamp($s) {
	return str_replace('&amp;', '&', $s);
}}




if(! function_exists('lang_selector')) {
function lang_selector() {
	global $lang;
	
	$langs = glob('view/*/strings.php');
	
	$lang_options = array();
	$selected = "";
	
	if(is_array($langs) && count($langs)) {
		$langs[] = '';
		if(! in_array('view/en/strings.php',$langs))
			$langs[] = 'view/en/';
		asort($langs);
		foreach($langs as $l) {
			if($l == '') {
				$lang_options[""] = t('default');
				continue;
			}
			$ll = substr($l,5);
			$ll = substr($ll,0,strrpos($ll,'/'));
			$selected = (($ll === $lang && (x($_SESSION, 'language'))) ? $ll : $selected);
			$lang_options[$ll]=$ll;
		}
	}

	$tpl = get_markup_template("lang_selector.tpl");	
	$o = replace_macros($tpl, array(
		'$title' => t('Select an alternate language'),
		'$langs' => array($lang_options, $selected),
		
	));
	return $o;
}}


if(! function_exists('return_bytes')) {
function return_bytes ($size_str) {
    switch (substr ($size_str, -1))
    {
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
}}

function generate_user_guid() {
	$found = true;
	do {
		$guid = random_string(16);
		$x = q("SELECT `uid` FROM `user` WHERE `guid` = '%s' LIMIT 1",
			dbesc($guid)
		);
		if(! count($x))
			$found = false;
	} while ($found == true );
	return $guid;
}



function base64url_encode($s, $strip_padding = false) {

	$s = strtr(base64_encode($s),'+/','-_');

	if($strip_padding)
		$s = str_replace('=','',$s);

	return $s;
}

function base64url_decode($s) {

	if(is_array($s)) {
		logger('base64url_decode: illegal input: ' . print_r(debug_backtrace(), true));
		return $s;
	}

/*
 *  // Placeholder for new rev of salmon which strips base64 padding.
 *  // PHP base64_decode handles the un-padded input without requiring this step
 *  // Uncomment if you find you need it.
 *
 *	$l = strlen($s);
 *	if(! strpos($s,'=')) {
 *		$m = $l % 4;
 *		if($m == 2)
 *			$s .= '==';
 *		if($m == 3)
 *			$s .= '=';
 *	}
 *
 */

	return base64_decode(strtr($s,'-_','+/'));
}


if (!function_exists('str_getcsv')) {
    function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = '\\', $eol = '\n') {
        if (is_string($input) && !empty($input)) {
            $output = array();
            $tmp    = preg_split("/".$eol."/",$input);
            if (is_array($tmp) && !empty($tmp)) {
                while (list($line_num, $line) = each($tmp)) {
                    if (preg_match("/".$escape.$enclosure."/",$line)) {
                        while ($strlen = strlen($line)) {
                            $pos_delimiter       = strpos($line,$delimiter);
                            $pos_enclosure_start = strpos($line,$enclosure);
                            if (
                                is_int($pos_delimiter) && is_int($pos_enclosure_start)
                                && ($pos_enclosure_start < $pos_delimiter)
                                ) {
                                $enclosed_str = substr($line,1);
                                $pos_enclosure_end = strpos($enclosed_str,$enclosure);
                                $enclosed_str = substr($enclosed_str,0,$pos_enclosure_end);
                                $output[$line_num][] = $enclosed_str;
                                $offset = $pos_enclosure_end+3;
                            } else {
                                if (empty($pos_delimiter) && empty($pos_enclosure_start)) {
                                    $output[$line_num][] = substr($line,0);
                                    $offset = strlen($line);
                                } else {
                                    $output[$line_num][] = substr($line,0,$pos_delimiter);
                                    $offset = (
                                                !empty($pos_enclosure_start)
                                                && ($pos_enclosure_start < $pos_delimiter)
                                                )
                                                ?$pos_enclosure_start
                                                :$pos_delimiter+1;
                                }
                            }
                            $line = substr($line,$offset);
                        }
                    } else {
                        $line = preg_split("/".$delimiter."/",$line);
   
                        /*
                         * Validating against pesky extra line breaks creating false rows.
                         */
                        if (is_array($line) && !empty($line[0])) {
                            $output[$line_num] = $line;
                        } 
                    }
                }
                return $output;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
} 

function cleardiv() {
	return '<div class="clear"></div>';
}


function bb_translate_video($s) {

	$matches = null;
	$r = preg_match_all("/\[video\](.*?)\[\/video\]/ism",$s,$matches,PREG_SET_ORDER);
	if($r) {
		foreach($matches as $mtch) {
			if((stristr($mtch[1],'youtube')) || (stristr($mtch[1],'youtu.be')))
				$s = str_replace($mtch[0],'[youtube]' . $mtch[1] . '[/youtube]',$s);
			elseif(stristr($mtch[1],'vimeo'))
				$s = str_replace($mtch[0],'[vimeo]' . $mtch[1] . '[/vimeo]',$s);
		}
	}
	return $s;	
}

function html2bb_video($s) {

	$s = preg_replace('#<object[^>]+>(.*?)https+://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+)(.*?)</object>#ism',
			'[youtube]$2[/youtube]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https+://www.youtube.com/embed/([A-Za-z0-9\-_=]+)(.*?)</iframe>#ism',
			'[youtube]$2[/youtube]', $s);

	$s = preg_replace('#<iframe[^>](.*?)https+://player.vimeo.com/video/([0-9]+)(.*?)</iframe>#ism',
			'[vimeo]$2[/vimeo]', $s);

	return $s;
}

/**
 * apply xmlify() to all values of array $val, recursively
 */
function array_xmlify($val){
	if (is_bool($val)) return $val?"true":"false";
	if (is_array($val)) return array_map('array_xmlify', $val);
	return xmlify((string) $val);
}


function reltoabs($text, $base)
{
  if (empty($base))
    return $text;

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

function item_post_type($item) {
	if(intval($item['event-id']))
		return t('event');
	if(strlen($item['resource-id']))
		return t('photo');
	if(strlen($item['verb']) && $item['verb'] !== ACTIVITY_POST)
		return t('activity');
	if($item['id'] != $item['parent'])
		return t('comment');
	return t('post');
}

// post categories and "save to file" use the same item.file table for storage.
// We will differentiate the different uses by wrapping categories in angle brackets
// and save to file categories in square brackets.
// To do this we need to escape these characters if they appear in our tag. 

function file_tag_encode($s) {
	return str_replace(array('<','>','[',']'),array('%3c','%3e','%5b','%5d'),$s);
}

function file_tag_decode($s) {
	return str_replace(array('%3c','%3e','%5b','%5d'),array('<','>','[',']'),$s);
}

function file_tag_file_query($table,$s,$type = 'file') {

	if($type == 'file')
		$str = preg_quote( '[' . str_replace('%','%%',file_tag_encode($s)) . ']' );
	else
		$str = preg_quote( '<' . str_replace('%','%%',file_tag_encode($s)) . '>' );
	return " AND " . (($table) ? dbesc($table) . '.' : '') . "file regexp '" . dbesc($str) . "' ";
}

// ex. given music,video return <music><video> or [music][video]
function file_tag_list_to_file($list,$type = 'file') {
        $tag_list = '';
        if(strlen($list)) {
                $list_array = explode(",",$list);
                if($type == 'file') {
	                $lbracket = '[';
	                $rbracket = ']';
	        }
                else {
	                $lbracket = '<';
        	        $rbracket = '>';
	        }

                foreach($list_array as $item) {
		  if(strlen($item)) {
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
        if($type == 'file') {
                $cnt = preg_match_all('/\[(.*?)\]/',$file,$matches,PREG_SET_ORDER);
	}
        else {
                $cnt = preg_match_all('/<(.*?)>/',$file,$matches,PREG_SET_ORDER);
	}
	if($cnt) {
		foreach($matches as $mtch) {
			if(strlen($list))
				$list .= ',';
			$list .= file_tag_decode($mtch[1]);
		}
	}

        return $list;
}

function file_tag_update_pconfig($uid,$file_old,$file_new,$type = 'file') {
        // $file_old - categories previously associated with an item
        // $file_new - new list of categories for an item

	if(! intval($uid))
		return false;

        if($file_old == $file_new)
	        return true;

	$saved = get_pconfig($uid,'system','filetags');
        if(strlen($saved)) {
                if($type == 'file') {
	                $lbracket = '[';
	                $rbracket = ']';
	        }
                else {
	                $lbracket = '<';
        	        $rbracket = '>';
	        }

                $filetags_updated = $saved;

		// check for new tags to be added as filetags in pconfig
                $new_tags = array();
                $check_new_tags = explode(",",file_tag_file_to_list($file_new,$type));

	        foreach($check_new_tags as $tag) {
		        if(! stristr($saved,$lbracket . file_tag_encode($tag) . $rbracket))
			        $new_tags[] = $tag;
	        }

		$filetags_updated .= file_tag_list_to_file(implode(",",$new_tags),$type);

		// check for deleted tags to be removed from filetags in pconfig
                $deleted_tags = array();
                $check_deleted_tags = explode(",",file_tag_file_to_list($file_old,$type));

	        foreach($check_deleted_tags as $tag) {
		        if(! stristr($file_new,$lbracket . file_tag_encode($tag) . $rbracket))
		                $deleted_tags[] = $tag;
	        }

                foreach($deleted_tags as $key => $tag) {
		        $r = q("select file from item where uid = %d " . file_tag_file_query('item',$tag,$type),
		                intval($uid)
	                );

	                if(count($r)) {
			        unset($deleted_tags[$key]);
	                }
			else {
			        $filetags_updated = str_replace($lbracket . file_tag_encode($tag) . $rbracket,'',$filetags_updated);
			}
		}

                if($saved != $filetags_updated) {
		        set_pconfig($uid,'system','filetags', $filetags_updated);
                }
		return true;
	}
        else
                if(strlen($file_new)) {
		        set_pconfig($uid,'system','filetags', $file_new);
                }
		return true;
}

function file_tag_save_file($uid,$item,$file) {
	$result = false;
	if(! intval($uid))
		return false;
	$r = q("select file from item where id = %d and uid = %d limit 1",
		intval($item),
		intval($uid)
	);
	if(count($r)) {
		if(! stristr($r[0]['file'],'[' . file_tag_encode($file) . ']'))
			q("update item set file = '%s' where id = %d and uid = %d limit 1",
				dbesc($r[0]['file'] . '[' . file_tag_encode($file) . ']'),
				intval($item),
				intval($uid)
			);
		$saved = get_pconfig($uid,'system','filetags');
		if((! strlen($saved)) || (! stristr($saved,'[' . file_tag_encode($file) . ']')))
			set_pconfig($uid,'system','filetags',$saved . '[' . file_tag_encode($file) . ']');
		info( t('Item filed') );
	}
	return true;
}

function file_tag_unsave_file($uid,$item,$file,$cat = false) {
	$result = false;
	if(! intval($uid))
		return false;

	if($cat == true)
		$pattern = '<' . file_tag_encode($file) . '>' ;
	else
		$pattern = '[' . file_tag_encode($file) . ']' ;


	$r = q("select file from item where id = %d and uid = %d limit 1",
		intval($item),
		intval($uid)
	);
	if(! count($r))
		return false;

	q("update item set file = '%s' where id = %d and uid = %d limit 1",
		dbesc(str_replace($pattern,'',$r[0]['file'])),
		intval($item),
		intval($uid)
	);

	$r = q("select file from item where uid = %d and deleted = 0 " . file_tag_file_query('item',$file,(($cat) ? 'category' : 'file')),
		intval($uid)
	);

	if(! count($r)) {
		$saved = get_pconfig($uid,'system','filetags');
		set_pconfig($uid,'system','filetags',str_replace($pattern,'',$saved));

	}
	return true;
}

function normalise_openid($s) {
	return trim(str_replace(array('http://','https://'),array('',''),$s),'/');
}


function undo_post_tagging($s) {
	$matches = null;
	$cnt = preg_match_all('/([@#])\[url=(.*?)\](.*?)\[\/url\]/ism',$s,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$s = str_replace($mtch[0], $mtch[1] . $mtch[3],$s);
		}
	}
	return $s;
}

function fix_mce_lf($s) {
	$s = str_replace("\r\n","\n",$s);
	$s = str_replace("\n\n","\n",$s);
	return $s;
}


function protect_sprintf($s) {
	return(str_replace('%','%%',$s));
}

