<?php


function viewsrc_content(App $a) {

	if (! local_user()) {
		notice( t('Access denied.') . EOL);
		return;
	}

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if(! $item_id) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
		return;
	}

	$r = q("SELECT `item`.`body` FROM `item`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		and `item`.`moderated` = 0
		AND `item`.`id` = '%s' LIMIT 1",
		intval(local_user()),
		dbesc($item_id)
	);

	if (dbm::is_result($r))
		if(is_ajax()) {
			echo str_replace("\n",'<br />',$r[0]['body']);
			killme();
		} else {
			$o .= str_replace("\n",'<br />',$r[0]['body']);
		}
	return $o;
}

