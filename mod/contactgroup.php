<?php

use Friendica\App;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;

function contactgroup_content(App $a)
{
	if (!local_user()) {
		killme();
	}

	$change = null;
	if (($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		$r = q("SELECT `id` FROM `contact` WHERE `id` = %d AND `uid` = %d and `self` = 0 and `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (DBM::isResult($r)) {
			$change = intval($a->argv[2]);
		}
	}

	if (($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (!DBM::isResult($r)) {
			killme();
		}

		$group = $r[0];
		$members = Contact::getByGroupId($group['id']);
		$preselected = [];
		if (count($members)) {
			foreach ($members as $member) {
				$preselected[] = $member['id'];
			}
		}

		if (x($change)) {
			if (in_array($change, $preselected)) {
				Group::removeMember($group['id'], $change);
			} else {
				Group::addMember($group['id'], $change);
			}
		}
	}

	killme();
}
