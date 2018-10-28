<?php
/**
 * @file mod/notify.php
 */

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\NotificationsManager;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Module\Login;
use Friendica\Util\Temporal;

function notify_init(App $a)
{
	if (! local_user()) {
		return;
	}

	$nm = new NotificationsManager();

	if ($a->argc > 2 && $a->argv[1] === 'view' && intval($a->argv[2])) {
		$note = $nm->getByID($a->argv[2]);
		if ($note) {
			$nm->setSeen($note);

			// The friendica client has problems with the GUID. this is some workaround
			if ($a->isFriendicaApp()) {
				require_once("include/items.php");
				$urldata = parse_url($note['link']);
				$guid = basename($urldata["path"]);
				$itemdata = Item::getIdAndNickByGuid($guid, local_user());
				if ($itemdata["id"] != 0) {
					$note['link'] = System::baseUrl().'/display/'.$itemdata["nick"].'/'.$itemdata["id"];
				}
			}

			System::externalRedirect($note['link']);
		}

		$a->internalRedirect();
	}

	if ($a->argc > 2 && $a->argv[1] === 'mark' && $a->argv[2] === 'all') {
		$r = $nm->setAllSeen();
		$j = json_encode(['result' => ($r) ? 'success' : 'fail']);
		echo $j;
		killme();
	}
}

function notify_content(App $a)
{
	if (! local_user()) {
		return Login::form();
	}

	$nm = new NotificationsManager();

	$notif_tpl = Text::getMarkupTemplate('notifications.tpl');

	$not_tpl = Text::getMarkupTemplate('notify.tpl');

	$r = $nm->getAll(['seen'=>0]);
	if (DBA::isResult($r) > 0) {
		foreach ($r as $it) {
			$notif_content .= Text::replaceMacros($not_tpl, [
				'$item_link' => System::baseUrl(true).'/notify/view/'. $it['id'],
				'$item_image' => $it['photo'],
				'$item_text' => strip_tags(BBCode::convert($it['msg'])),
				'$item_when' => Temporal::getRelativeDate($it['date'])
			]);
		}
	} else {
		$notif_content .= L10n::t('No more system notifications.');
	}

	$o = Text::replaceMacros($notif_tpl, [
		'$notif_header' => L10n::t('System Notifications'),
		'$tabs' => false, // $tabs,
		'$notif_content' => $notif_content,
	]);

	return $o;
}
