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

}