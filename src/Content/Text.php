<?php
/**
 * @file src/Content/Text.php
 */
namespace Friendica\Content;

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Smilies;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Render\FriendicaSmarty;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Proxy as ProxyUtils;

require_once "include/conversation.php";

/**
 * @brief ContactSelector class
 */
class Text
{
    /**
     * This is our template processor
     *
     * @param string|FriendicaSmarty $s the string requiring macro substitution,
     *				or an instance of FriendicaSmarty
    * @param array $r key value pairs (search => replace)
    * @return string substituted string
    */
    function replaceMacros($s, $r) {

        $stamp1 = microtime(true);

        $a = get_app();

        // pass $baseurl to all templates
        $r['$baseurl'] = System::baseUrl();

        $t = $a->getTemplateEngine();
        try {
            $output = $t->replaceMacros($s, $r);
        } catch (Exception $e) {
            echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
            killme();
        }

        $a->saveTimestamp($stamp1, "rendering");

        return $output;
    }

    /**
     * @brief Generates a pseudo-random string of hexadecimal characters
     *
     * @param int $size
     * @return string
     */
    function randomString($size = 64)
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
    function noTags($string) {
        return str_replace(["<", ">"], ['[', ']'], $string);
    }

    /**
     * use this on "body" or "content" input where angle chars shouldn't be removed,
     * and allow them to be safely displayed.
     * @param string $string
     * @return string
     */
    function escapeTags($string) {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
    }

    /**
     * generate a string that's random, but usually pronounceable.
     * used to generate initial passwords
     * @param int $len
     * @return string
     */
    function autoName($len) {

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
                $word = autoName($len);
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
        $ret = htmlspecialchars_decode($s, ENT_QUOTES);
        return $ret;
    }

    /**
     * Loader for infinite scrolling
     * @return string html for loader
     */
    function scrollLoader() {
        $tpl = self::getMarkupTemplate("scroll_loader.tpl");
        return self::replaceMacros($tpl, [
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
    function expandAcl($s) {
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
    function sanitiseAcl(&$item) {
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
    function perms2Str($p) {
        $ret = '';
        if (is_array($p)) {
            $tmp = $p;
        } else {
            $tmp = explode(',', $p);
        }

        if (is_array($tmp)) {
            array_walk($tmp, 'Text::sanitiseAcl');
            $ret = implode('', $tmp);
        }
        return $ret;
    }

    /**
     * load template $s
     *
     * @param string $s
     * @param string $root
     * @return string
     */
    function getMarkupTemplate($s, $root = '') {
        $stamp1 = microtime(true);

        $a = get_app();
        $t = $a->getTemplateEngine();
        try {
            $template = $t->getTemplateFile($s, $root);
        } catch (Exception $e) {
            echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
            killme();
        }

        $a->saveTimestamp($stamp1, "file");

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
    function attributeContains($attr, $s) {
        $a = explode(' ', $attr);
        return (count($a) && in_array($s,$a));
    }

    /**
     * @brief Logs the given message at the given log level
     *
     * log levels:
     * LOGGER_WARNING
     * LOGGER_INFO (default)
     * LOGGER_TRACE
     * LOGGER_DEBUG
     * LOGGER_DATA
     * LOGGER_ALL
     *
     * @global array $LOGGER_LEVELS
     * @param string $msg
     * @param int $level
     */
    function logger($msg, $level = LOGGER_INFO) {
        $a = get_app();
        global $LOGGER_LEVELS;
        $LOGGER_LEVELS = [];

        $debugging = Config::get('system', 'debugging');
        $logfile   = Config::get('system', 'logfile');
        $loglevel = intval(Config::get('system', 'loglevel'));

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
        $a->saveTimestamp($stamp1, "file");
    }

    /**
     * @brief An alternative logger for development.
     * Works largely as logger() but allows developers
     * to isolate particular elements they are targetting
     * personally without background noise
     *
     * log levels:
     * LOGGER_WARNING
     * LOGGER_INFO (default)
     * LOGGER_TRACE
     * LOGGER_DEBUG
     * LOGGER_DATA
     * LOGGER_ALL
     *
     * @global array $LOGGER_LEVELS
     * @param string $msg
     * @param int $level
     */
    function dlogger($msg, $level = LOGGER_INFO) {
        $a = get_app();

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
            $process_id = $a->process_id;
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
        $a->saveTimestamp($stamp1, "file");
    }

    /**
     * Compare activity uri. Knows about activity namespace.
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    function activityMatch($haystack, $needle) {
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
    function getTags($string) {
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
    function contactBlock() {
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
                DBA::escape(Protocol::DFRN),
                DBA::escape(Protocol::OSTATUS),
                DBA::escape(Protocol::DIASPORA)
        );
        if (DBA::isResult($r)) {
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
                    DBA::escape(Protocol::DFRN),
                    DBA::escape(Protocol::OSTATUS),
                    DBA::escape(Protocol::DIASPORA),
                    intval($shown)
            );
            if (DBA::isResult($r)) {
                $contacts = [];
                foreach ($r AS $contact) {
                    $contacts[] = $contact["id"];
                }
                $r = q("SELECT `id`, `uid`, `addr`, `url`, `name`, `thumb`, `network` FROM `contact` WHERE `id` IN (%s)",
                    DBA::escape(implode(",", $contacts)));

                if (DBA::isResult($r)) {
                    $contacts = L10n::tt('%d Contact', '%d Contacts', $total);
                    $micropro = [];
                    foreach ($r as $rr) {
                        $micropro[] = self::micropro($rr, true, 'mpfriend');
                    }
                }
            }
        }

        $tpl = self::getMarkupTemplate('contact_block.tpl');
        $o = self::replaceMacros($tpl, [
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

        return self::replaceMacros(self::getMarkupTemplate(($textmode)?'micropro_txt.tpl':'micropro_img.tpl'),[
            '$click' => defaults($contact, 'click', ''),
            '$class' => $class,
            '$url' => $url,
            '$photo' => ProxyUtils::proxifyUrl($contact['thumb'], false, ProxyUtils::SIZE_THUMB),
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

        return self::replaceMacros(self::getMarkupTemplate('searchbox.tpl'), $values);
    }

    /**
     * @brief Check for a valid email string
     *
     * @param string $email_address
     * @return boolean
     */
    function validEmail($email_address)
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
    function getPokeVerbs() {

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
    function dayTranslate($s) {
        $ret = str_replace(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
            [L10n::t('Monday'), L10n::t('Tuesday'), L10n::t('Wednesday'), L10n::t('Thursday'), L10n::t('Friday'), L10n::t('Saturday'), L10n::t('Sunday')],
            $s);

        $ret = str_replace(['January','February','March','April','May','June','July','August','September','October','November','December'],
            [L10n::t('January'), L10n::t('February'), L10n::t('March'), L10n::t('April'), L10n::t('May'), L10n::t('June'), L10n::t('July'), L10n::t('August'), L10n::t('September'), L10n::t('October'), L10n::t('November'), L10n::t('December')],
            $ret);

        return $ret;
    }

}