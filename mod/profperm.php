<?php

function profperm_init(App &$a) {

	if (! local_user()) {
		return;
	}

	$which = $a->user['nickname'];
	$profile = $a->argv[1];

	profile_load($a,$which,$profile);

}


function profperm_content(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied') . EOL);
		return;
	}


	if($a->argc < 2) {
		notice( t('Invalid profile identifier.') . EOL );
		return;
	}

	// Switch to text mod interface if we have more than 'n' contacts or group members

	$switchtotext = get_pconfig(local_user(),'system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = get_config('system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = 400;


	if(($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		$r = q("SELECT `id` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `self` = 0
			AND `network` = '%s' AND `id` = %d AND `uid` = %d LIMIT 1",
			dbesc(NETWORK_DFRN),
			intval($a->argv[2]),
			intval(local_user())
		);
		if (dbm::is_result($r))
			$change = intval($a->argv[2]);
	}


	if(($a->argc > 1) && (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `profile` WHERE `id` = %d AND `uid` = %d AND `is-default` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice( t('Invalid profile identifier.') . EOL );
			return;
		}
		$profile = $r[0];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `profile-id` = %d",
			intval(local_user()),
			intval($a->argv[1])
		);

		$ingroup = array();
		if (dbm::is_result($r))
			foreach($r as $member)
				$ingroup[] = $member['id'];

		$members = $r;

		if($change) {
			if(in_array($change,$ingroup)) {
				q("UPDATE `contact` SET `profile-id` = 0 WHERE `id` = %d AND `uid` = %d",
					intval($change),
					intval(local_user())
				);
			}
			else {
				q("UPDATE `contact` SET `profile-id` = %d WHERE `id` = %d AND `uid` = %d",
					intval($a->argv[1]),
					intval($change),
					intval(local_user())
				);

			}

			$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `profile-id` = %d",
				intval(local_user()),
				intval($a->argv[1])
			);

			$members = $r;

			$ingroup = array();
			if (dbm::is_result($r))
				foreach($r as $member)
					$ingroup[] = $member['id'];
		}

		$o .= '<h2>' . t('Profile Visibility Editor') . '</h2>';

		$o .= '<h3>' . t('Profile') . ' \'' . $profile['profile-name'] . '\'</h3>';

		$o .= '<div id="prof-edit-desc">' . t('Click on a contact to add or remove.') . '</div>';

	}

	$o .= '<div id="prof-update-wrapper">';
	if($change) 
		$o = '';
	
	$o .= '<div id="prof-members-title">';
	$o .= '<h3>' . t('Visible To') . '</h3>';
	$o .= '</div>';
	$o .= '<div id="prof-members">';

	$textmode = (($switchtotext && (count($members) > $switchtotext)) ? true : false);

	foreach($members as $member) {
		if($member['url']) {
			$member['click'] = 'profChangeMember(' . $profile['id'] . ',' . $member['id'] . '); return true;';
			$o .= micropro($member,true,'mpprof', $textmode);
		}
	}
	$o .= '</div><div id="prof-members-end"></div>';
	$o .= '<hr id="prof-separator" />';

	$o .= '<div id="prof-all-contcts-title">';
	$o .= '<h3>' . t("All Contacts \x28with secure profile access\x29") . '</h3>';
	$o .= '</div>';
	$o .= '<div id="prof-all-contacts">';

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 and `pending` = 0 and `self` = 0
			AND `network` = '%s' ORDER BY `name` ASC",
			intval(local_user()),
			dbesc(NETWORK_DFRN)
		);

		if (dbm::is_result($r)) {
			$textmode = (($switchtotext && (count($r) > $switchtotext)) ? true : false);
			foreach($r as $member) {
				if(! in_array($member['id'],$ingroup)) {
					$member['click'] = 'profChangeMember(' . $profile['id'] . ',' . $member['id'] . '); return true;';
					$o .= micropro($member,true,'mpprof',$textmode);
				}
			}
		}

		$o .= '</div><div id="prof-all-contacts-end"></div>';

	if($change) {
		echo $o;
		killme();
	}
	$o .= '</div>';
	return $o;

}

