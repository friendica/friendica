<?php
function share_init(&$a) {

	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if((! $post_id) || (! local_user()))
		killme();

	$r = q("SELECT item.*, contact.network FROM `item` 
		inner join contact on `item`.`contact-id` = `contact`.`id` 
		WHERE `item`.`id` = %d AND `item`.`uid` = %d LIMIT 1",

		intval($post_id),
		intval(local_user())
	);
	if(! count($r) || ($r[0]['private'] == 1))
		killme();

	if (!intval(get_config('system','old_share'))) {
		if (strpos($r[0]['body'], "[/share]") !== false) {
			$pos = strpos($r[0]['body'], "[share");
			$o = substr($r[0]['body'], $pos);
		} else {
			$o = share_header($r[0]['author-name'], $r[0]['author-link'], $r[0]['author-avatar'], $r[0]['guid'], $r[0]['created'], $r[0]['plink']);

			if($r[0]['title'])
				$o .= '[b]'.$r[0]['title'].'[/b]'."\n";
			$o .= $r[0]['body'];
			$o.= "[/share]";
		}
	} else {
		$o = '';

		$o .= "\xE2\x99\xb2" . ' [url=' . $r[0]['author-link'] . ']' . $r[0]['author-name'] . '[/url]' . "\n";
		if($r[0]['title'])
			$o .= '[b]' . $r[0]['title'] . '[/b]' . "\n";
		$o .= $r[0]['body'] . "\n" ;

		$o .= (($r[0]['plink']) ? '[url=' . $r[0]['plink'] . ']' . t('link') . '[/url]' . "\n" : '');
	}
	echo $o;
	killme();
}

function share_header($author, $profile, $avatar, $guid, $posted, $link) {
	$header = "[share author='".str_replace(array("'", "[", "]"), array("&#x27;", "&#x5B;", "&#x5D;"),$author).
		"' profile='".str_replace(array("'", "[", "]"), array("&#x27;", "&#x5B;", "&#x5D;"),$profile).
		"' avatar='".str_replace(array("'", "[", "]"), array("&#x27;", "&#x5B;", "&#x5D;"),$avatar);

	if ($guid)
		$header .= "' guid='".str_replace(array("'", "[", "]"), array("&#x27;", "&#x5B;", "&#x5D;"),$guid);

	if ($posted)
		$header .= "' posted='".str_replace(array("'", "[", "]"), array("&#x27;", "&#x5B;", "&#x5D;"),$posted);

	$header .= "' link='".str_replace(array("'", "[", "]"), array("&#x27;", "&#x5B;", "&#x5D;"),$link)."']";

	return $header;
}
