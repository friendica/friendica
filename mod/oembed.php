<?php
require_once("include/oembed.php");

function oembed_content(&$a){
	// logger('mod_oembed ' . $a->query_string, LOGGER_ALL);

	if ($a->argv[1]=='b2h'){
		$url = array( "", trim(hex2bin($_REQUEST['url'])));
		echo oembed_replacecb($url);
		killme();
	}
	
	if ($a->argv[1]=='h2b'){
		$text = trim(hex2bin($_REQUEST['text']));
		echo oembed_html2bbcode($text);
		killme();
	}
	
	if ($a->argc == 2){
		echo "<html><body>";
		$url = base64url_decode($a->argv[1]);
		$j = oembed_fetch_url($url);
		echo $j->html;
//		logger('mod-oembed ' . $j->html, LOGGER_ALL);
		echo "</body></html>";
	}
	killme();
}
