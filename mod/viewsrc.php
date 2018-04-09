<?php
/**
 * @file mod/viewsrc.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\DBM;

function viewsrc_content(App $a) {

	if (! local_user()) {
		notice(L10n::t('Access denied.') . EOL);
		return;
	}

	$o = ''; 
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if(! $item_id) {
		$a->error = 404;
		notice(L10n::t('Item not found.') . EOL);
		return;
	}

	$r = q("SELECT `item`.`body` FROM `item`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		and `item`.`moderated` = 0
		AND `item`.`id` = '%s' LIMIT 1",
		intval(local_user()),
		dbesc($item_id)
	);

	if (DBM::is_result($r))
		if(is_ajax()) {
			echo str_replace("\n",'<br />',$r[0]['body']);
			killme();
		} else {
			$o .= str_replace("\n",'<br />',$r[0]['body']);
		}
	return $o;
}

