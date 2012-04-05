<?php


function manage_post(&$a) {

	if(! local_user())
		return;

	$uid = local_user();
	$orig_record = $a->user;

	if((x($_SESSION,'submanage')) && intval($_SESSION['submanage'])) {
		$r = q("select * from user where uid = %d limit 1",
			intval($_SESSION['submanage'])
		);
		if(count($r)) {
			$uid = intval($r[0]['uid']);
			$orig_record = $r[0];
		}
	}

	$r = q("select * from manage where uid = %d",
		intval($uid)
	);

	$submanage = $r;

	$identity = ((x($_POST['identity'])) ? intval($_POST['identity']) : 0);
	if(! $identity)
		return;

	$limited_id = 0;
	$original_id = $uid;

	if(count($submanage)) {
		foreach($submanage as $m) {
			if($identity == $m['mid']) {
				$limited_id = $m['mid'];
				break;
			}
		}
	}

	if($limited_id) {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($limited_id)
		);
	}
	else {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d AND `email` = '%s' AND `password` = '%s' LIMIT 1",
			intval($identity),
			dbesc($orig_record['email']),
			dbesc($orig_record['password'])
		);
	}

	if(! count($r))
		return;

	unset($_SESSION['authenticated']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['page_flags']);
	unset($_SESSION['return_url']);
	if(x($_SESSION,'submanage'))
		unset($_SESSION['submanage']);

	require_once('include/security.php');
	authenticate_success($r[0],true,true);

	if($limited_id)
		$_SESSION['submanage'] = $original_id;

	goaway($a->get_baseurl(true) . '/profile/' . $a->user['nickname']);
	// NOTREACHED
}



function manage_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$o = '<h3>' . t('Manage Identities and/or Pages') . '</h3>';

	
	$o .= '<div id="identity-manage-desc">' . t('Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions') . '</div>';

	$o .= '<div id="identity-manage-choose">' . t('Select an identity to manage: ') . '</div>';

	$o .= '<div id="identity-selector-wrapper">' . "\r\n";
	$o .= '<form action="manage" method="post" >' . "\r\n";
	$o .= '<select name="identity" size="4">' . "\r\n";

	foreach($a->identities as $rr) {
		$selected = (($rr['nickname'] === $a->user['nickname']) ? ' selected="selected" ' : '');
		$o .= '<option ' . $selected . 'value="' . $rr['uid'] . '">' . $rr['username'] . ' (' . $rr['nickname'] . ')</option>' . "\r\n";
	}

	$o .= '</select>' . "\r\n";
	$o .= '<div id="identity-select-break"></div>' . "\r\n";

	$o .= '<input id="identity-submit" type="submit" name="submit" value="' . t('Submit') . '" /></div></form>' . "\r\n";

	return $o;

}
