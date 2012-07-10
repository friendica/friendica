<?php

require_once('bbcode.php');

function share_init(&$a) {

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if((! $post_id) || (! local_user()))
		killme();

	$r = q("SELECT item.*, contact.network FROM `item` 
		left join contact on `item`.`contact-id` = `contact`.`id` 
		WHERE `item`.`id` = %d AND `item`.`uid` = %d LIMIT 1",

		intval($post_id),
		intval(local_user())
	);
	if(! count($r) || ($r[0]['private'] == 1))
		killme();

	$o = '';

	$o .= "\xE2\x99\xb2" . ' [url=' . $r[0]['author-link'] . ']' . $r[0]['author-name'] . '[/url]' . "\n";
	if($r[0]['title'])
		$o .= '[b]' . ' [url=' . $r[0]['plink'] . ']' . $r[0]['title'] . '[/url]' . '[/b]' . "\n";
        else
                $o .= '[b]' . ' [url=' . $r[0]['plink'] . ']' . 'View Source' . '[/url]' . '[/b]' . "\n";
	$o .= $r[0]['body'] . "\n";
        
        echo $o;

	killme();  
}