<?php
require_once("include/oembed.php");

function oembed_content(App $a) {
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

		// workaround for media.ccc.de (and any other endpoint that return size 0)
		if (substr($j->html, 0, 7) == "<iframe" && strstr($j->html, 'width="0"')) {
			$j->html = '<style>html,body{margin:0;padding:0;} iframe{width:100%;height:100%;}</style>'. $j->html;
			$j->html = str_replace('width="0"', '', $j->html);
			$j->html = str_replace('height="0"', '', $j->html);
		}
		echo $j->html;
//		logger('mod-oembed ' . $j->html, LOGGER_ALL);
		echo "</body></html>";
	}
	killme();
}
