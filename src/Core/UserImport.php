<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core;

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Object\Image;
use Friendica\Domain\Repository\PermissionSet;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;

/**
 * UserImport class
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
	 * @param array &$arr   Column=>Value array from json (by ref)
	 * @throws \Exception
	 */
	private static function checkCols($table, &$arr)
	{
		$tableColumns = DBStructure::getColumns($table);

		$tcols = [];
		$ttype = [];
		// get a plain array of column names
		foreach ($tableColumns as $tcol) {
			$tcols[] = $tcol['Field'];
			$ttype[$tcol['Field']] = $tcol['Type'];
		}
		// remove inexistent columns
		foreach ($arr as $icol => $ival) {
			if (!in_array($icol, $tcols)) {
				unset($arr[$icol]);
				continue;
			}

			if ($ttype[$icol] === 'datetime') {
				$arr[$icol] = $ival ?? DBA::NULL_DATETIME;
			}
		}
	}

	/**
	 * Import data into table $table
	 *
	 * @param string $table Table name
	 * @param array  $arr   Column=>Value array from json
	 * @return array|bool
	 * @throws \Exception
	 */
	private static function dbImportAssoc($table, $arr)
	{
		if (isset($arr['id'])) {
			unset($arr['id']);
		}

		self::checkCols($table, $arr);

		if (self::IMPORT_DEBUG) {
			return true;
		}

		return DBA::insert($table, $arr);
	}

	/**
	 * Import account file exported from mod/uexport
	 *
	 * @param array $file array from $_FILES
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function importAccount($file)
	{
		Logger::log("Start user import from " . $file['tmp_name']);
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
			notice(DI::l10n()->t("Error decoding account file"));
			return;
		}


		if (empty($account['version'])) {
			notice(DI::l10n()->t("Error! No version data in file! This is not a Friendica account file?"));
			return;
		}

		// check for username
		// check if username matches deleted account
		if (DBA::exists('user', ['nickname' => $account['user']['nickname']])
			|| DBA::exists('userd', ['username' => $account['user']['nickname']])) {
			notice(DI::l10n()->t("User '%s' already exists on this server!", $account['user']['nickname']));
			return;
		}

		$oldbaseurl = $account['baseurl'];
		$newbaseurl = DI::baseUrl();

		$oldaddr = str_replace('http://', '@', Strings::normaliseLink($oldbaseurl));
		$newaddr = str_replace('http://', '@', Strings::normaliseLink($newbaseurl));

		if (!empty($account['profile']['addr'])) {
			$old_handle = $account['profile']['addr'];
		} else {
			$old_handle = $account['user']['nickname'].$oldaddr;
		}

		// Creating a new guid to avoid problems with Diaspora
		$account['user']['guid'] = System::createUUID();

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
			Logger::log("uimport:insert user : ERROR : " . DBA::errorMessage(), Logger::INFO);
			notice(DI::l10n()->t("User creation error"));
			return;
		}
		$newuid = self::lastInsertId();

		DI::pConfig()->set($newuid, 'system', 'previous_addr', $old_handle);

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
				$contact["avatar-date"] = DBA::NULL_DATETIME;

				switch ($contact['network']) {
					case Protocol::DFRN:
					case Protocol::DIASPORA:
						//  send relocate message (below)
						break;
					case Protocol::FEED:
					case Protocol::MAIL:
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
				Logger::log("uimport:insert contact " . $contact['nick'] . "," . $contact['network'] . " : ERROR : " . DBA::errorMessage(), Logger::INFO);
				$errorcount++;
			} else {
				$contact['newid'] = self::lastInsertId();
			}
		}
		if ($errorcount > 0) {
			notice(DI::l10n()->tt("%d contact not imported", "%d contacts not imported", $errorcount));
		}

		foreach ($account['group'] as &$group) {
			$group['uid'] = $newuid;
			$r = self::dbImportAssoc('group', $group);
			if ($r === false) {
				Logger::log("uimport:insert group " . $group['name'] . " : ERROR : " . DBA::errorMessage(), Logger::INFO);
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
					Logger::log("uimport:insert group member " . $group_member['id'] . " : ERROR : " . DBA::errorMessage(), Logger::INFO);
				}
			}
		}

		foreach ($account['profile'] as &$profile) {
			unset($profile['id']);
			$profile['uid'] = $newuid;

			foreach ($profile as $k => &$v) {
				$v = str_replace([$oldbaseurl, $oldaddr], [$newbaseurl, $newaddr], $v);
				foreach (["profile", "avatar"] as $k) {
					$v = str_replace($oldbaseurl . "/photo/" . $k . "/" . $olduid . ".jpg", $newbaseurl . "/photo/" . $k . "/" . $newuid . ".jpg", $v);
				}
			}

			if (count($account['profile']) === 1 || $profile['is-default']) {
				$r = self::dbImportAssoc('profile', $profile);

				if ($r === false) {
					Logger::log("uimport:insert profile: ERROR : " . DBA::errorMessage(), Logger::INFO);
					notice(DI::l10n()->t("User profile creation error"));
					DBA::delete('user', ['uid' => $newuid]);
					DBA::delete('profile_field', ['uid' => $newuid]);
					return;
				}

				$profile['id'] = DBA::lastInsertId();
			}

			DI::profileField()->migrateFromLegacyProfile($profile);
		}

		///@TODO Replace with permissionset import
		$self_contact = Contact::selectFirst(['id'], ['uid' => $newuid, 'self' => true]);
		$allow_cid = DI::aclFormatter()->toString($self_contact['id']);
		$self_psid = DI::permissionSet()->getIdFromACL($newuid, $allow_cid);

		foreach ($account['profile_fields'] ?? [] as $profile_field) {
			$profile_field['uid'] = $newuid;

			///@TODO Replace with permissionset import
			$profile_field['psid'] = $profile_field['psid'] ? $self_psid : PermissionSet::PUBLIC;

			if (self::dbImportAssoc('profile_field', $profile_field) === false) {
				Logger::info("uimport:insert profile field " . $profile_field['id'] . " : ERROR : " . DBA::errorMessage());
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
				Logger::log("uimport:insert photo " . $photo['resource-id'] . "," . $photo['scale'] . " : ERROR : " . DBA::errorMessage(), Logger::INFO);
			}
		}

		foreach ($account['pconfig'] as &$pconfig) {
			$pconfig['uid'] = $newuid;
			$r = self::dbImportAssoc('pconfig', $pconfig);
			if ($r === false) {
				Logger::log("uimport:insert pconfig " . $pconfig['id'] . " : ERROR : " . DBA::errorMessage(), Logger::INFO);
			}
		}

		// send relocate messages
		Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, $newuid);

		info(DI::l10n()->t("Done. You can now login with your username and password"));
		DI::baseUrl()->redirect('login');
	}
}
