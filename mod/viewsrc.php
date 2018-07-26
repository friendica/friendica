<?php
/**
 * @file mod/viewsrc.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Model\Item;

function viewsrc_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Access denied.') . EOL);
		return;
	}

	$o = '';
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (!$item_id) {
		$a->error = 404;
		notice(L10n::t('Item not found.') . EOL);
		return;
	}

	$item = Item::selectFirst(['body'], ['uid' => local_user(), 'id' => $item_id]);

	if (DBA::isResult($item)) {
		if (is_ajax()) {
			echo str_replace("\n", '<br />', $item['body']);
			killme();
		} else {
			$o .= str_replace("\n", '<br />', $item['body']);
		}
	}
	return $o;
}
