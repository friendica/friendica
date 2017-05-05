<?php
/**
 * Read strings.php file and create messages.po
 *
 * php utils/php2po.php <path/to/strings.php>
 *
 * Output to <path/to/messages.po>
 */

use Friendica\App;

DEFINE("NORM_REGEXP", "|[\\\]|");

if(! class_exists('App')) {
	class TmpA {
		public $strings = Array();
	}
	$a = new TmpA();
}

if ($argc<2 || in_array('-h', $argv) || in_array('--h', $argv)) {
	print "Usage: ".$argv[0]." [-p <n>] <strings.php>\n\n";
	print "Options:\n";
	print "p\tNumber of plural forms. Default: 2\n";
	print "\n";
	return;
}

$phpfile = $argv[1];
$pofile = dirname($phpfile)."/messages.po";

if (!file_exists($phpfile)){
	print "Unable to find '$phpfile'\n";
	return;
}

// utility functions
function startsWith($haystack, $needle) {
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}


// start !
include_once($phpfile);

$out  = '';
$out .= "# FRIENDICA Distributed Social Network\n";
$out .= "# Copyright (C) 2010, 2011, 2012, 2013 the Friendica Project\n";
$out .= "# This file is distributed under the same license as the Friendica package.\n";
$out .= "# \n";
$out .= 'msgid ""' ."\n";
$out .= 'msgstr ""' ."\n";
$out .= '"Project-Id-Version: friendica\n"' ."\n";
$out .= '"Report-Msgid-Bugs-To: \n"' ."\n";
$out .= '"POT-Creation-Date: '. date("Y-m-d H:i:sO").'\n"' ."\n";
$out .= '"MIME-Version: 1.0\n"' ."\n";
$out .= '"Content-Type: text/plain; charset=UTF-8\n"' ."\n";
$out .= '"Content-Transfer-Encoding: 8bit\n"' ."\n";

// search for plural info
$lang = "";
$lang_logic = "";
$lang_pnum = 2;

$_idx = array_search('-p', $argv);
if ($_idx !== false) {
	$lang_pnum = $argv[$_idx+1];
}

$infile = file($phpfile);
foreach($infile as $l) {
	$l = trim($l);
	if  (startsWith($l, 'function string_plural_select_')) {
		$lang = str_replace( 'function string_plural_select_' , '', str_replace( '($n){','', $l) );
	}
	if (startsWith($l, 'return')) {
		$lang_logic = str_replace( '$', '', trim( str_replace( 'return ' , '',  $l) , ';') );
		break;
	}
}

echo "Language: $lang\n";
echo "Plural forms: $lang_pnum\n";
echo "Plural logic: $lang_logic;\n";

$out .= sprintf('"Language: %s\n"', $lang) ."\n";
$out .= sprintf('"Plural-Forms: nplurals=%s; plural=%s;\n"', $lang_pnum, $lang_logic)  ."\n";
$out .= "\n";

print "\nLoading base message.po...";

// load base messages.po and extract msgids
$base_msgids = array();
$norm_base_msgids = array();
$base_f = file("util/messages.po") or die("No base messages.po\n");
$_f = 0; $_mid = ""; $_mids = array();
foreach( $base_f as $l) {
	$l = trim($l);
	//~ print $l."\n";

	if (startsWith($l, 'msgstr')) {
		if ($_mid != '""') {
			$base_msgids[$_mid] =  $_mids;
			$norm_base_msgids[preg_replace(NORM_REGEXP, "", $_mid)] = $_mid;
			//~ print "\t\t\t".$_mid. print_r($base_msgids[$_mid], true);
		}

		$_f = 0;
		$_mid = "";
		$_mids = array();

	}

	if (startsWith($l, '"') && $_f==2) {
		$_mids[count($_mids)-1] .= "\n".$l;
		//~ print "\t\t+mids: ".print_t($_mids, true);
	}
	if (startsWith($l, 'msgid_plural ')) {
		$_f = 2;
		$_mids[] = str_replace('msgid_plural ', '' , $l);
		//~ print "\t\t mids: ".print_r($_mids, true);
	}

	if (startsWith($l, '"') && $_f==1) {
		$_mid .= "\n".$l;
		$_mids[count($_mids)-1] .= "\n".$l;
		//~ print "\t+mid: $_mid \n";
	}
	if (startsWith($l, 'msgid ')) {
		$_f = 1;
		$_mid = str_replace('msgid ', '' , $l);
			$_mids = array($_mid);
		//~ print "\t mid: $_mid \n";
	}
	//~ print "\t\t\t\t$_f\n\n";
}

print " done\n";
print "Creating '$pofile'...";
// create msgid and msgstr

/**
 * Get a string and retun a message.po ready text
 * - replace " with \"
 * - replace tab char with \t
 * - manage multiline strings
 */
function massage_string($str) {
	$str = str_replace('\\','\\\\',$str);
	$str = str_replace('"','\"',$str);
	$str = str_replace("\t",'\t',$str);
	$str = str_replace("\n",'\n"'."\n".'"',$str);
	if (strpos($str, "\n")!==false  && $str[0]!=='"') $str = '"'."\n".$str;
	$str = preg_replace("|\n([^\"])|", "\n\"$1", $str);
	return sprintf('"%s"', $str);
}

function find_original_msgid($str) {
	global $norm_base_msgids;
	$norm_str = preg_replace(NORM_REGEXP, "", $str);
	if (array_key_exists($norm_str, $norm_base_msgids)) {
		return $norm_base_msgids[$norm_str];
	}
	return $str;
}

$warnings = "";
foreach($a->strings as $key=>$str) {
	$msgid = massage_string($key);

	if (preg_match("|%[sd0-9](\$[sn])*|", $msgid)) {
		$out .= "#, php-format\n";
	}
	$msgid = find_original_msgid($msgid);
	$out .= 'msgid '. $msgid . "\n";

	if (is_array($str)) {
		if (array_key_exists($msgid, $base_msgids) && isset($base_msgids[$msgid][1]))  {
			$out .= 'msgid_plural '. $base_msgids[$msgid][1] . "\n";
		} else {
			$out .= 'msgid_plural '. $msgid . "\n";
			$warnings .= "[W] No source plural form for msgid:\n". str_replace("\n","\n\t", $msgid) . "\n\n";
		}
		foreach ( $str as $n => $msgstr) {
			$out .= 'msgstr['.$n.'] '. massage_string($msgstr) . "\n";
		}
	} else {
		$out .= 'msgstr '. massage_string($str) . "\n";
	}

	$out .= "\n";

}

file_put_contents($pofile, $out);

print " done\n";

if ($warnings=="") {
	print "No warnings.\n";
} else {
	print $warnings;
}
