<?php
/**
 * @file mod/manage.php
 */
use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Authentication;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;

require_once "include/text.php";

function manage_post(App $a) {

	if (! local_user()) {
		return;
	}

	$uid = local_user();
	$orig_record = $a->user;

	if((x($_SESSION,'submanage')) && intval($_SESSION['submanage'])) {
		$r = q("select * from user where uid = %d limit 1",
			intval($_SESSION['submanage'])
		);
		if (DBA::isResult($r)) {
			$uid = intval($r[0]['uid']);
			$orig_record = $r[0];
		}
	}

	$r = q("SELECT * FROM `manage` WHERE `uid` = %d",
		intval($uid)
	);

	$submanage = $r;

	$identity = (x($_POST['identity']) ? intval($_POST['identity']) : 0);
	if (!$identity) {
		return;
	}

	$limited_id = 0;
	$original_id = $uid;

	if (DBA::isResult($submanage)) {
		foreach ($submanage as $m) {
			if ($identity == $m['mid']) {
				$limited_id = $m['mid'];
				break;
			}
		}
	}

	if ($limited_id) {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($limited_id)
		);
	} else {
		// Check if the target user is one of our children
		$r = q("SELECT * FROM `user` WHERE `uid` = %d AND `parent-uid` = %d LIMIT 1",
			intval($identity),
			DBA::escape($orig_record['uid'])
		);

		// Check if the target user is one of our siblings
		if (!DBA::isResult($r) && ($orig_record['parent-uid'] != 0)) {
			$r = q("SELECT * FROM `user` WHERE `uid` = %d AND `parent-uid` = %d LIMIT 1",
				intval($identity),
				DBA::escape($orig_record['parent-uid'])
			);
		}

		// Check if it's our parent
		if (!DBA::isResult($r) && ($orig_record['parent-uid'] != 0) && ($orig_record['parent-uid'] == $identity)) {
			$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
				intval($identity)
			);
		}

		// Finally check if it's out own user
		if (!DBA::isResult($r) && ($orig_record['uid'] != 0) && ($orig_record['uid'] == $identity)) {
			$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
				intval($identity)
			);
		}
	}

	if (!DBA::isResult($r)) {
		return;
	}

	unset($_SESSION['authenticated']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['mobile-theme']);
	unset($_SESSION['page_flags']);
	unset($_SESSION['return_path']);
	if (x($_SESSION, 'submanage')) {
		unset($_SESSION['submanage']);
	}
	if (x($_SESSION, 'sysmsg')) {
		unset($_SESSION['sysmsg']);
	}
	if (x($_SESSION, 'sysmsg_info')) {
		unset($_SESSION['sysmsg_info']);
	}

	Authentication::setAuthenticatedSessionForUser($r[0], true, true);

	if ($limited_id) {
		$_SESSION['submanage'] = $original_id;
	}

	$ret = [];
	Addon::callHooks('home_init',$ret);

	$a->internalRedirect('profile/' . $a->user['nickname'] );
	// NOTREACHED
}



function manage_content(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (!empty($_GET['identity'])) {
		$_POST['identity'] = $_GET['identity'];
		manage_post($a);
		return;
	}

	$identities = $a->identities;

	//getting additinal information for each identity
	foreach ($identities as $key=>$id) {
		$thumb = q("SELECT `thumb` FROM `contact` WHERE `uid` = '%s' AND `self` = 1",
			DBA::escape($id['uid'])
		);

		$identities[$key]['thumb'] = $thumb[0]['thumb'];

		$identities[$key]['selected'] = ($id['nickname'] === $a->user['nickname']);

		$notifications = 0;

		$r = q("SELECT DISTINCT(`parent`) FROM `notify` WHERE `uid` = %d AND NOT `seen` AND NOT (`type` IN (%d, %d))",
			intval($id['uid']), intval(NOTIFY_INTRO), intval(NOTIFY_MAIL));

		if (DBA::isResult($r)) {
			$notifications = sizeof($r);
		}

		$r = q("SELECT DISTINCT(`convid`) FROM `mail` WHERE `uid` = %d AND NOT `seen`",
			intval($id['uid']));

		if (DBA::isResult($r)) {
			$notifications = $notifications + sizeof($r);
		}

		$r = q("SELECT COUNT(*) AS `introductions` FROM `intro` WHERE NOT `blocked` AND NOT `ignore` AND `uid` = %d",
			intval($id['uid']));

		if (DBA::isResult($r)) {
			$notifications = $notifications + $r[0]["introductions"];
		}

		$identities[$key]['notifications'] = $notifications;
	}

	$o = Text::replaceMacros(Text::getMarkupTemplate('manage.tpl'), [
		'$title' => L10n::t('Manage Identities and/or Pages'),
		'$desc' => L10n::t('Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'),
		'$choose' => L10n::t('Select an identity to manage: '),
		'$identities' => $identities,
		'$submit' => L10n::t('Submit'),
	]);

	return $o;

}
