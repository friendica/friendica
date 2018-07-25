<?php
/**
 * @file mod/lockview.php
 */
use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Model\Item;

function lockview_content(App $a) {

	$type = (($a->argc > 1) ? $a->argv[1] : 0);
	if (is_numeric($type)) {
		$item_id = intval($type);
		$type='item';
	} else {
		$item_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);
	}

	if (!$item_id)
		killme();

	if (!in_array($type, ['item','photo','event']))
		killme();

	$fields = ['uid', 'private', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
	$condition = ['id' => $item_id];
	if ($type != 'item') {
		$item = DBA::selectFirst($type, $fields, $condition);
	} else {
		$item = Item::selectFirst($fields, $condition);
	}

	if (!DBA::isResult($item)) {
		killme();
	}

	Addon::callHooks('lockview_content', $item);

	if ($item['uid'] != local_user()) {
		echo L10n::t('Remote privacy information not available.') . '<br />';
		killme();
	}


	if (($item['private'] == 1) && empty($item['allow_cid']) && empty($item['allow_gid'])
		&& empty($item['deny_cid']) && empty($item['deny_gid'])) {

		echo L10n::t('Remote privacy information not available.') . '<br />';
		killme();
	}

	$allowed_users = expand_acl($item['allow_cid']);
	$allowed_groups = expand_acl($item['allow_gid']);
	$deny_users = expand_acl($item['deny_cid']);
	$deny_groups = expand_acl($item['deny_gid']);

	$o = L10n::t('Visible to:') . '<br />';
	$l = [];

	if (count($allowed_groups)) {
		$r = q("SELECT `name` FROM `group` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ', $allowed_groups))
		);
		if (DBA::isResult($r))
			foreach($r as $rr)
				$l[] = '<b>' . $rr['name'] . '</b>';
	}
	if (count($allowed_users)) {
		$r = q("SELECT `name` FROM `contact` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ',$allowed_users))
		);
		if (DBA::isResult($r))
			foreach($r as $rr)
				$l[] = $rr['name'];

	}

	if (count($deny_groups)) {
		$r = q("SELECT `name` FROM `group` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ', $deny_groups))
		);
		if (DBA::isResult($r))
			foreach($r as $rr)
				$l[] = '<b><strike>' . $rr['name'] . '</strike></b>';
	}
	if (count($deny_users)) {
		$r = q("SELECT `name` FROM `contact` WHERE `id` IN ( %s )",
			DBA::escape(implode(', ',$deny_users))
		);
		if (DBA::isResult($r))
			foreach($r as $rr)
				$l[] = '<strike>' . $rr['name'] . '</strike>';

	}

	echo $o . implode(', ', $l);
	killme();

}
