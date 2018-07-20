<?php
/**
 * @file src/Core/UserImport.php
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Model\Photo;
use Friendica\Object\Image;

require_once "include/dba.php";

/**
 * @brief UserImport class
 */
class UserImport
{
	const IMPORT_DEBUG = false;

	private static function lastInsertId()
	{
		if (self::IMPORT_DEBUG) {
			return 1;
		}

		return DBA::lastInsertId();
	}

	/**
	 * Remove columns from array $arr that aren't in table $table
	 *
	 * @param string $table Table name
	 * @param array &$arr Column=>Value array from json (by ref)
	 */
	private static function checkCols($table, &$arr)
	{
		$query = sprintf("SHOW COLUMNS IN `%s`", dbesc($table));
		logger("uimport: $query", LOGGER_DEBUG);
		$r = q($query);
		$tcols = [];
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
	private static function dbImportAssoc($table, $arr)
	{
		if (isset($arr['id'])) {
			unset($arr['id']);
		}

		self::checkCols($table, $arr);
		$cols = implode("`,`", array_map('dbesc', array_keys($arr)));
		$vals = implode("','", array_map('dbesc', array_values($arr)));
		$query = "INSERT INTO `$table` (`$cols`) VALUES ('$vals')";
		logger("uimport: $query", LOGGER_TRACE);

		if (self::IMPORT_DEBUG) {
			return true;
		}

		return q($query);
	}

	/**
	 * @brief Import account file exported from mod/uexport
	 *
	 * @param App $a Friendica App Class
	 * @param array $file array from $_FILES
	 */
	public static function importAccount(App $a, $file)
	{
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
			notice(L10n::t("Error decoding account file"));
			return;
		}


		if (!x($account, 'version')) {
			notice(L10n::t("Error! No version data in file! This is not a Friendica account file?"));
			return;
		}

		// check for username
		// check if username matches deleted account
		if (DBA::exists('user', ['nickname' => $account['user']['nickname']])
			|| DBA::exists('userd', ['username' => $account['user']['nickname']])) {
			notice(L10n::t("User '%s' already exists on this server!", $account['user']['nickname']));
			return;
		}

		$oldbaseurl = $account['baseurl'];
		$newbaseurl = System::baseUrl();

		$oldaddr = str_replace('http://', '@', normalise_link($oldbaseurl));
		$newaddr = str_replace('http://', '@', normalise_link($newbaseurl));

		if (!empty($account['profile']['addr'])) {
			$old_handle = $account['profile']['addr'];
		} else {
			$old_handle = $account['user']['nickname'].$oldaddr;
		}

		$olduid = $account['user']['uid'];

		unset($account['user']['uid']);
		unset($account['user']['account_expired']);
		unset($account['user']['account_expires_on']);
		unset($account['user']['expire_notification_sent']);

		$callback = function (&$value) use ($oldbaseurl, $oldaddr, $newbaseurl, $newaddr) {
			$value =  str_replace([$oldbaseurl, $oldaddr], [$newbaseurl, $newaddr], $value);
		};

		array_walk($account['user'], $callback);

		// import user
		$r = self::dbImportAssoc('user', $account['user']);
		if ($r === false) {
			logger("uimport:insert user : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
			notice(L10n::t("User creation error"));
			return;
		}
		$newuid = self::lastInsertId();

		PConfig::set($newuid, 'system', 'previous_addr', $old_handle);

		foreach ($account['profile'] as &$profile) {
			foreach ($profile as $k => &$v) {
				$v = str_replace([$oldbaseurl, $oldaddr], [$newbaseurl, $newaddr], $v);
				foreach (["profile", "avatar"] as $k) {
					$v = str_replace($oldbaseurl . "/photo/" . $k . "/" . $olduid . ".jpg", $newbaseurl . "/photo/" . $k . "/" . $newuid . ".jpg", $v);
				}
			}
			$profile['uid'] = $newuid;
			$r = self::dbImportAssoc('profile', $profile);
			if ($r === false) {
				logger("uimport:insert profile " . $profile['profile-name'] . " : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
				info(L10n::t("User profile creation error"));
				DBA::delete('user', ['uid' => $newuid]);
				return;
			}
		}

		$errorcount = 0;
		foreach ($account['contact'] as &$contact) {
			if ($contact['uid'] == $olduid && $contact['self'] == '1') {
				foreach ($contact as $k => &$v) {
					$v = str_replace([$oldbaseurl, $oldaddr], [$newbaseurl, $newaddr], $v);
					foreach (["profile", "avatar", "micro"] as $k) {
						$v = str_replace($oldbaseurl . "/photo/" . $k . "/" . $olduid . ".jpg", $newbaseurl . "/photo/" . $k . "/" . $newuid . ".jpg", $v);
					}
				}
			}
			if ($contact['uid'] == $olduid && $contact['self'] == '0') {
				// set contacts 'avatar-date' to NULL_DATE to let worker to update urls
				$contact["avatar-date"] = NULL_DATE;

				switch ($contact['network']) {
					case NETWORK_DFRN:
					case NETWORK_DIASPORA:
						//  send relocate message (below)
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
			$r = self::dbImportAssoc('contact', $contact);
			if ($r === false) {
				logger("uimport:insert contact " . $contact['nick'] . "," . $contact['network'] . " : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
				$errorcount++;
			} else {
				$contact['newid'] = self::lastInsertId();
			}
		}
		if ($errorcount > 0) {
			notice(L10n::tt("%d contact not imported", "%d contacts not imported", $errorcount));
		}

		foreach ($account['group'] as &$group) {
			$group['uid'] = $newuid;
			$r = self::dbImportAssoc('group', $group);
			if ($r === false) {
				logger("uimport:insert group " . $group['name'] . " : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
			} else {
				$group['newid'] = self::lastInsertId();
			}
		}

		foreach ($account['group_member'] as &$group_member) {
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
				$r = self::dbImportAssoc('group_member', $group_member);
				if ($r === false) {
					logger("uimport:insert group member " . $group_member['id'] . " : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
				}
			}
		}

		foreach ($account['photo'] as &$photo) {
			$photo['uid'] = $newuid;
			$photo['data'] = hex2bin($photo['data']);

			$Image = new Image($photo['data'], $photo['type']);
			$r = Photo::store(
				$Image,
				$photo['uid'], $photo['contact-id'], //0
				$photo['resource-id'], $photo['filename'], $photo['album'], $photo['scale'], $photo['profile'], //1
				$photo['allow_cid'], $photo['allow_gid'], $photo['deny_cid'], $photo['deny_gid']
			);

			if ($r === false) {
				logger("uimport:insert photo " . $photo['resource-id'] . "," . $photo['scale'] . " : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
			}
		}

		foreach ($account['pconfig'] as &$pconfig) {
			$pconfig['uid'] = $newuid;
			$r = self::dbImportAssoc('pconfig', $pconfig);
			if ($r === false) {
				logger("uimport:insert pconfig " . $pconfig['id'] . " : ERROR : " . DBA::errorMessage(), LOGGER_NORMAL);
			}
		}

		// send relocate messages
		Worker::add(PRIORITY_HIGH, 'Notifier', 'relocate', $newuid);

		info(L10n::t("Done. You can now login with your username and password"));
		goaway(System::baseUrl() . "/login");
	}
}
