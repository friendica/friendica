<?php

/**
 * import account file exported from mod/uexport
 * args:
 *  $a       App     Friendica App Class
 *  $file   Array   array from $_FILES
 */
require_once("include/Photo.php");
define("IMPORT_DEBUG", False);

function last_insert_id() {
	global $db;

	if (IMPORT_DEBUG)
		return 1;

	return $db->insert_id();
}

function last_error() {
	global $db;
	return $db->error;
}

/**
 * Remove columns from array $arr that aren't in table $table
 *
 * @param string $table Table name
 * @param array &$arr Column=>Value array from json (by ref)
 */
function check_cols($table, &$arr) {
	$query = sprintf("SHOW COLUMNS IN `%s`", dbesc($table));
	logger("uimport: $query", LOGGER_DEBUG);
	$r = q($query);
	$tcols = array();
	// get a plain array of column names
	foreach ($r as $tcol) {
		$tcols[] = $tcol['Field'];
	}
	// remove inexistent columns
	foreach ($arr as $icol => $ival) {
		if (!in_array($icol, $tcols)) {
			unset($arr[$icol]);
		}
	}
}

/**
 * Import data into table $table
 *
 * @param string $table Table name
 * @param array $arr Column=>Value array from json
 */
function db_import_assoc($table, $arr) {
	if (isset($arr['id']))
		unset($arr['id']);
	check_cols($table, $arr);
	$cols = implode("`,`", array_map('dbesc', array_keys($arr)));
	$vals = implode("','", array_map('dbesc', array_values($arr)));
	$query = "INSERT INTO `$table` (`$cols`) VALUES ('$vals')";
	logger("uimport: $query", LOGGER_TRACE);
	if (IMPORT_DEBUG)
		return true;
	return q($query);
}

function import_cleanup($newuid) {
	q("DELETE FROM `user` WHERE uid = %d", $newuid);
	q("DELETE FROM `contact` WHERE uid = %d", $newuid);
	q("DELETE FROM `profile` WHERE uid = %d", $newuid);
	q("DELETE FROM `photo` WHERE uid = %d", $newuid);
	q("DELETE FROM `group` WHERE uid = %d", $newuid);
	q("DELETE FROM `group_member` WHERE uid = %d", $newuid);
	q("DELETE FROM `pconfig` WHERE uid = %d", $newuid);
}

function import_account(App $a, $file) {
	logger("Start user import from " . $file['tmp_name']);
	/*
	  STEPS
	  1. checks
	  2. replace old baseurl with new baseurl
	  3. import data (look at user id and contacts id)
	  4. archive non-dfrn contacts
	  5. send message to dfrn contacts
	 */

	$account = json_decode(file_get_contents($file['tmp_name']), true);
	if ($account === null) {
		notice(t("Error decoding account file"));
		return;
	}


	if (!x($account, 'version')) {
		notice(t("Error! No version data in file! This is not a Friendica account file?"));
		return;
	}

	/*
	// this is not required as we remove columns in json not in current db schema
	if ($account['schema'] != DB_UPDATE_VERSION) {
		notice(t("Error! I can't import this file: DB schema version is not compatible."));
		return;
	}
	*/

	// check for username
	$r = q("SELECT uid FROM user WHERE nickname='%s'", $account['user']['nickname']);
	if ($r === false) {
		logger("uimport:check nickname : ERROR : " . last_error(), LOGGER_NORMAL);
		notice(t('Error! Cannot check nickname'));
		return;
	}
	if (dbm::is_result($r) > 0) {
		notice(sprintf(t("User '%s' already exists on this server!"), $account['user']['nickname']));
		return;
	}
	// check if username matches deleted account
	$r = q("SELECT id FROM userd WHERE username='%s'", $account['user']['nickname']);
	if ($r === false) {
		logger("uimport:check nickname : ERROR : " . last_error(), LOGGER_NORMAL);
		notice(t('Error! Cannot check nickname'));
		return;
	}
	if (dbm::is_result($r) > 0) {
		notice(sprintf(t("User '%s' already exists on this server!"), $account['user']['nickname']));
		return;
	}

	$oldbaseurl = $account['baseurl'];
	$newbaseurl = App::get_baseurl();
	$olduid = $account['user']['uid'];

        unset($account['user']['uid']);
        unset($account['user']['account_expired']);
        unset($account['user']['account_expires_on']);
        unset($account['user']['expire_notification_sent']);
	foreach ($account['user'] as $k => &$v) {
		$v = str_replace($oldbaseurl, $newbaseurl, $v);
	}


	// import user
	$r = db_import_assoc('user', $account['user']);
	if ($r === false) {
		//echo "<pre>"; var_dump($r, $query, mysql_error()); killme();
		logger("uimport:insert user : ERROR : " . last_error(), LOGGER_NORMAL);
		notice(t("User creation error"));
		return;
	}
	$newuid = last_insert_id();
	//~ $newuid = 1;

	// Generate a new guid for the account. Otherwise there will be problems with diaspora
	q("UPDATE `user` SET `guid` = '%s' WHERE `uid` = %d",
		dbesc(generate_user_guid()), intval($newuid));

	foreach ($account['profile'] as &$profile) {
		foreach ($profile as $k => &$v) {
			$v = str_replace($oldbaseurl, $newbaseurl, $v);
			foreach (array("profile", "avatar") as $k)
				$v = str_replace($oldbaseurl . "/photo/" . $k . "/" . $olduid . ".jpg", $newbaseurl . "/photo/" . $k . "/" . $newuid . ".jpg", $v);
		}
		$profile['uid'] = $newuid;
		$r = db_import_assoc('profile', $profile);
		if ($r === false) {
			logger("uimport:insert profile " . $profile['profile-name'] . " : ERROR : " . last_error(), LOGGER_NORMAL);
			info(t("User profile creation error"));
			import_cleanup($newuid);
			return;
		}
	}

	$errorcount = 0;
	foreach ($account['contact'] as &$contact) {
		if ($contact['uid'] == $olduid && $contact['self'] == '1') {
			foreach ($contact as $k => &$v) {
				$v = str_replace($oldbaseurl, $newbaseurl, $v);
				foreach (array("profile", "avatar", "micro") as $k)
					$v = str_replace($oldbaseurl . "/photo/" . $k . "/" . $olduid . ".jpg", $newbaseurl . "/photo/" . $k . "/" . $newuid . ".jpg", $v);
			}
		}
		if ($contact['uid'] == $olduid && $contact['self'] == '0') {
			// set contacts 'avatar-date' to NULL_DATE to let poller to update urls
			$contact["avatar-date"] = NULL_DATE;


			switch ($contact['network']) {
				case NETWORK_DFRN:
					//  send relocate message (below)
					break;
				case NETWORK_ZOT:
					/// @TODO handle zot network
					break;
				case NETWORK_MAIL2:
					/// @TODO ?
					break;
				case NETWORK_FEED:
				case NETWORK_MAIL:
					// Nothing to do
					break;
				default:
					// archive other contacts
					$contact['archive'] = "1";
			}
		}
		$contact['uid'] = $newuid;
		$r = db_import_assoc('contact', $contact);
		if ($r === false) {
			logger("uimport:insert contact " . $contact['nick'] . "," . $contact['network'] . " : ERROR : " . last_error(), LOGGER_NORMAL);
			$errorcount++;
		} else {
			$contact['newid'] = last_insert_id();
		}
	}
	if ($errorcount > 0) {
		notice(sprintf(tt("%d contact not imported", "%d contacts not imported", $errorcount), $errorcount));
	}

	foreach ($account['group'] as &$group) {
		$group['uid'] = $newuid;
		$r = db_import_assoc('group', $group);
		if ($r === false) {
			logger("uimport:insert group " . $group['name'] . " : ERROR : " . last_error(), LOGGER_NORMAL);
		} else {
			$group['newid'] = last_insert_id();
		}
	}

	foreach ($account['group_member'] as &$group_member) {
		$group_member['uid'] = $newuid;

		$import = 0;
		foreach ($account['group'] as $group) {
			if ($group['id'] == $group_member['gid'] && isset($group['newid'])) {
				$group_member['gid'] = $group['newid'];
				$import++;
				break;
			}
		}
		foreach ($account['contact'] as $contact) {
			if ($contact['id'] == $group_member['contact-id'] && isset($contact['newid'])) {
				$group_member['contact-id'] = $contact['newid'];
				$import++;
				break;
			}
		}
		if ($import == 2) {
			$r = db_import_assoc('group_member', $group_member);
			if ($r === false) {
				logger("uimport:insert group member " . $group_member['id'] . " : ERROR : " . last_error(), LOGGER_NORMAL);
			}
		}
	}





	foreach ($account['photo'] as &$photo) {
		$photo['uid'] = $newuid;
		$photo['data'] = hex2bin($photo['data']);

		$p = new Photo($photo['data'], $photo['type']);
		$r = $p->store(
				$photo['uid'], $photo['contact-id'], //0
				$photo['resource-id'], $photo['filename'], $photo['album'], $photo['scale'], $photo['profile'], //1
				$photo['allow_cid'], $photo['allow_gid'], $photo['deny_cid'], $photo['deny_gid']
		);

		if ($r === false) {
			logger("uimport:insert photo " . $photo['resource-id'] . "," . $photo['scale'] . " : ERROR : " . last_error(), LOGGER_NORMAL);
		}
	}

	foreach ($account['pconfig'] as &$pconfig) {
		$pconfig['uid'] = $newuid;
		$r = db_import_assoc('pconfig', $pconfig);
		if ($r === false) {
			logger("uimport:insert pconfig " . $pconfig['id'] . " : ERROR : " . last_error(), LOGGER_NORMAL);
		}
	}

	// send relocate messages
	proc_run(PRIORITY_HIGH, 'include/notifier.php', 'relocate', $newuid);

	info(t("Done. You can now login with your username and password"));
	goaway(App::get_baseurl() . "/login");
}
