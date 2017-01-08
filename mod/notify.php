<?php
require_once('include/NotificationsManager.php');

function notify_init(App &$a) {
	if (! local_user()) {
		return;
	}

	$nm = new NotificationsManager();

	if ($a->argc > 2 && $a->argv[1] === 'view' && intval($a->argv[2])) {
		$note = $nm->getByID($a->argv[2]);
		if ($note) {
			$nm->setSeen($note);
		
			// The friendica client has problems with the GUID. this is some workaround
			if ($a->is_friendica_app()) {
				require_once("include/items.php");
				$urldata = parse_url($note['link']);
				$guid = basename($urldata["path"]);
				$itemdata = get_item_id($guid, local_user());
				if ($itemdata["id"] != 0) {
					$note['link'] = App::get_baseurl().'/display/'.$itemdata["nick"].'/'.$itemdata["id"];
				}
			}

			goaway($note['link']);
		}

		goaway(App::get_baseurl(true));
	}

	if ($a->argc > 2 && $a->argv[1] === 'mark' && $a->argv[2] === 'all' ) {
		$r = $nm->setAllSeen();
		$j = json_encode(array('result' => ($r) ? 'success' : 'fail'));
		echo $j;
		killme();
	}

}

function notify_content(App &$a) {
	if (! local_user()) {
		return login();
	}

	$nm = new NotificationsManager();
	
	$notif_tpl = get_markup_template('notifications.tpl');

	$not_tpl = get_markup_template('notify.tpl');
	require_once('include/bbcode.php');

	$r = $nm->getAll(array('seen'=>0));
	if (dbm::is_result($r) > 0) {
		foreach ($r as $it) {
			$notif_content .= replace_macros($not_tpl,array(
				'$item_link' => App::get_baseurl(true).'/notify/view/'. $it['id'],
				'$item_image' => $it['photo'],
				'$item_text' => strip_tags(bbcode($it['msg'])),
				'$item_when' => relative_date($it['date'])
			));
		}
	} else {
		$notif_content .= t('No more system notifications.');
	}

	$o .= replace_macros($notif_tpl, array(
		'$notif_header' => t('System Notifications'),
		'$tabs' => false, // $tabs,
		'$notif_content' => $notif_content,
	));

	return $o;


}
