<?php

require_once('bbcode.php');

function share_init(&$a) {

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if((! $post_id) || (! local_user()))
		killme();

	$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
		intval($post_id)
	);
	if(! count($r) || $r[0]['private'])
		killme();

	$o = '';

//	if(local_user() && intval(get_pconfig(local_user(),'system','plaintext'))) {
		$o .= "\xE2\x99\xb2" . ' [url=' . $r[0]['author-link'] . ']' . $r[0]['author-name'] . '[/url]' . "\n";
		if($r[0]['title'])
			$o .= '[b]' . $r[0]['title'] . '[/b]' . "\n";
		$o .= $r[0]['body'] . "\n";
//	}
//	else {
//		$o .= '&#x2672; <a href="' . $r[0]['author-link'] . '">' . $r[0]['author-name'] . '</a><br />';
//		if($r[0]['title'])
//			$o .= '<strong>' . $r[0]['title'] . '</strong><br />';
//		$o .= $r[0]['body'] . "\n";
//	}
	echo $o;
	killme();  
}
