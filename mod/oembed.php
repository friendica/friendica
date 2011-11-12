<?php
require_once("include/oembed.php");

function oembed_content(&$a){
	if ($a->argc == 2){
		echo "<html><body>";
		$url = base64url_decode($a->argv[1]);
		$j = oembed_fetch_url($url);
		echo $j->html;
		echo "</body></html>";
	}
	killme();
}
