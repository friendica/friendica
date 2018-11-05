<?php
/**
 * @file mod/profperm.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Model\Profile;

function profperm_init(App $a)
{
	if (!Session::user()->isLocal()) {
		return;
	}

	$which = $a->user['nickname'];
	$profile = $a->argv[1];

	Profile::load($a, $which, $profile);
}


function profperm_content(App $a) {

	if (!Session::user()->isLocal()) {
		notice(L10n::t('Permission denied') . EOL);
		return;
	}


	if ($a->argc < 2) {
		notice(L10n::t('Invalid profile identifier.') . EOL );
		return;
	}

	$o = '';

	// Switch to text mod interface if we have more than 'n' contacts or group members

	$switchtotext = PConfig::get(Session::user()->getUid(),'system','groupedit_image_limit');
	if (is_null($switchtotext)) {
		$switchtotext = Config::get('system','groupedit_image_limit', 400);
	}

	if (($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		$r = q("SELECT `id` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `self` = 0
			AND `network` = '%s' AND `id` = %d AND `uid` = %d LIMIT 1",
			DBA::escape(Protocol::DFRN),
			intval($a->argv[2]),
			Session::user()->getUid()
		);

		if (DBA::isResult($r)) {
			$change = intval($a->argv[2]);
		}
	}


	if (($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is-default` = 0 LIMIT 1",
			intval($a->argv[1]),
			Session::user()->getUid()
		);
		if (!DBA::isResult($r)) {
			notice(L10n::t('Invalid profile identifier.') . EOL );
			return;
		}
		$profile = $r[0];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `profile-id` = %d",
			Session::user()->getUid(),
			intval($a->argv[1])
		);

		$ingroup = [];
		if (DBA::isResult($r))
			foreach($r as $member)
				$ingroup[] = $member['id'];

		$members = $r;

		if (!empty($change)) {
			if (in_array($change,$ingroup)) {
				q("UPDATE `contact` SET `profile-id` = 0 WHERE `id` = %d AND `uid` = %d",
					intval($change),
					Session::user()->getUid()
				);
			}
			else {
				q("UPDATE `contact` SET `profile-id` = %d WHERE `id` = %d AND `uid` = %d",
					intval($a->argv[1]),
					intval($change),
					Session::user()->getUid()
				);

			}

			$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `profile-id` = %d",
				Session::user()->getUid(),
				intval($a->argv[1])
			);

			$members = $r;

			$ingroup = [];
			if (DBA::isResult($r))
				foreach($r as $member)
					$ingroup[] = $member['id'];
		}

		$o .= '<h2>' . L10n::t('Profile Visibility Editor') . '</h2>';

		$o .= '<h3>' . L10n::t('Profile') . ' \'' . $profile['profile-name'] . '\'</h3>';

		$o .= '<div id="prof-edit-desc">' . L10n::t('Click on a contact to add or remove.') . '</div>';

	}

	$o .= '<div id="prof-update-wrapper">';
	if (!empty($change))
		$o = '';

	$o .= '<div id="prof-members-title">';
	$o .= '<h3>' . L10n::t('Visible To') . '</h3>';
	$o .= '</div>';
	$o .= '<div id="prof-members">';

	$textmode = (($switchtotext && (count($members) > $switchtotext)) ? true : false);

	foreach($members as $member) {
		if ($member['url']) {
			$member['click'] = 'profChangeMember(' . $profile['id'] . ',' . $member['id'] . '); return true;';
			$o .= micropro($member,true,'mpprof', $textmode);
		}
	}
	$o .= '</div><div id="prof-members-end"></div>';
	$o .= '<hr id="prof-separator" />';

	$o .= '<div id="prof-all-contcts-title">';
	$o .= '<h3>' . L10n::t("All Contacts \x28with secure profile access\x29") . '</h3>';
	$o .= '</div>';
	$o .= '<div id="prof-all-contacts">';

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 and `pending` = 0 and `self` = 0
			AND `network` = '%s' ORDER BY `name` ASC",
			Session::user()->getUid(),
			DBA::escape(Protocol::DFRN)
		);

		if (DBA::isResult($r)) {
			$textmode = (($switchtotext && (count($r) > $switchtotext)) ? true : false);
			foreach($r as $member) {
				if (!in_array($member['id'],$ingroup)) {
					$member['click'] = 'profChangeMember(' . $profile['id'] . ',' . $member['id'] . '); return true;';
					$o .= micropro($member,true,'mpprof',$textmode);
				}
			}
		}

		$o .= '</div><div id="prof-all-contacts-end"></div>';

	if (!empty($change)) {
		echo $o;
		killme();
	}
	$o .= '</div>';
	return $o;

}
