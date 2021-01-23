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
 * Automatic post-databse structure change updates
 *
 * These functions are responsible for doing critical post update changes to the data (not the structure) in the database.
 *
 * Database structure changes are done in static/dbstructure.config.php
 *
 * For non-critical database migrations, please add a method in the Database\PostUpdate class
 *
 * If there is a need for a post update to a structure change, update this file
 * by adding a new function at the end with the number of the new DB_UPDATE_VERSION.
 *
 * The numbered script in this file has to be exactly like the DB_UPDATE_VERSION
 *
 * Example:
 * You are currently on version 4711 and you are preparing changes that demand an update script.
 *
 * 1. Create a function "update_4712()" here in the update.php
 * 2. Apply the needed structural changes in static/dbStructure.php
 * 3. Set DB_UPDATE_VERSION in static/dbstructure.config.php to 4712.
 *
 * If you need to run a script before the database update, name the function "pre_update_4712()"
 */

use Friendica\Core\Logger;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Notification;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Storage;
use Friendica\Worker\Delivery;

// Post-update script of PR 5751
function update_1298()
{
	$keys = ['gender', 'marital', 'sexual'];
	foreach ($keys as $translateKey) {
		$allData = DBA::select('profile', ['id', $translateKey]);
		$allLangs = DI::l10n()->getAvailableLanguages();
		$success = 0;
		$fail = 0;
		foreach ($allData as $key => $data) {
			$toTranslate = $data[$translateKey];
			if ($toTranslate != '') {
				foreach ($allLangs as $key => $lang) {
					$a = new \stdClass();
					$a->strings = [];

					// First we get the the localizations
					if (file_exists("view/lang/$lang/strings.php")) {
						include "view/lang/$lang/strings.php";
					}
					if (file_exists("addon/morechoice/lang/$lang/strings.php")) {
						include "addon/morechoice/lang/$lang/strings.php";
					}

					$localizedStrings = $a->strings;
					unset($a);

					$key = array_search($toTranslate, $localizedStrings);
					if ($key !== false) {
						break;
					}

					// defaulting to empty string
					$key = '';
				}

				if ($key == '') {
					$fail++;
				} else {
					DBA::update('profile', [$translateKey => $key], ['id' => $data['id']]);
					Logger::notice('Updated contact', ['action' => 'update', 'contact' => $data['id'], "$translateKey" => $key,
						'was' => $data[$translateKey]]);
					Worker::add(PRIORITY_LOW, 'ProfileUpdate', $data['id']);
					Contact::updateSelfFromUserID($data['id']);
					$success++;
				}
			}
		}

		Logger::notice($translateKey . " fix completed", ['action' => 'update', 'translateKey' => $translateKey, 'Success' => $success, 'Fail' => $fail ]);
	}
	return Update::SUCCESS;
}

function update_1309()
{
	$queue = DBA::select('queue', ['id', 'cid', 'guid']);
	while ($entry = DBA::fetch($queue)) {
		$contact = DBA::selectFirst('contact', ['uid'], ['id' => $entry['cid']]);
		if (!DBA::isResult($contact)) {
			continue;
		}

		$item = Post::selectFirst(['id', 'gravity'], ['uid' => $contact['uid'], 'guid' => $entry['guid']]);
		if (!DBA::isResult($item)) {
			continue;
		}

		$deliver_options = ['priority' => PRIORITY_MEDIUM, 'dont_fork' => true];
		Worker::add($deliver_options, 'Delivery', Delivery::POST, $item['id'], $entry['cid']);
		Logger::info('Added delivery worker', ['item' => $item['id'], 'contact' => $entry['cid']]);
		DBA::delete('queue', ['id' => $entry['id']]);
	}
	return Update::SUCCESS;
}

function update_1315()
{
	if (DBStructure::existsTable('item-delivery-data')) {
		DBA::delete('item-delivery-data', ['postopts' => '', 'inform' => '', 'queue_count' => 0, 'queue_done' => 0]);
	}
	return Update::SUCCESS;
}

function update_1318()
{
	DBA::update('profile', ['marital' => "In a relation"], ['marital' => "Unavailable"]);
	DBA::update('profile', ['marital' => "Single"], ['marital' => "Available"]);

	Worker::add(PRIORITY_LOW, 'ProfileUpdate');
	return Update::SUCCESS;
}

function update_1323()
{
	$users = DBA::select('user', ['uid']);
	while ($user = DBA::fetch($users)) {
		Contact::updateSelfFromUserID($user['uid']);
	}
	DBA::close($users);

	return Update::SUCCESS;
}

function update_1327()
{
	$contacts = DBA::select('contact', ['uid', 'id', 'blocked', 'readonly'], ["`uid` != ? AND (`blocked` OR `readonly`) AND NOT `pending`", 0]);
	while ($contact = DBA::fetch($contacts)) {
		Contact\User::setBlocked($contact['id'], $contact['uid'], $contact['blocked']);
		Contact\User::setIgnored($contact['id'], $contact['uid'], $contact['readonly']);
	}
	DBA::close($contacts);

	return Update::SUCCESS;
}

function update_1330()
{
	$currStorage = DI::config()->get('storage', 'class', '');

	// set the name of the storage instead of the classpath as config
	if (!empty($currStorage)) {
		/** @var Storage\IStorage $currStorage */
		if (!DI::config()->set('storage', 'name', $currStorage::getName())) {
			return Update::FAILED;
		}

		// try to delete the class since it isn't needed. This won't work with config files
		DI::config()->delete('storage', 'class');
	}

	// Update attachments and photos
	if (!DBA::p("UPDATE `photo` SET `photo`.`backend-class` = SUBSTR(`photo`.`backend-class`, 25) WHERE `photo`.`backend-class` LIKE 'Friendica\\\Model\\\Storage\\\%' ESCAPE '|'") ||
	    !DBA::p("UPDATE `attach` SET `attach`.`backend-class` = SUBSTR(`attach`.`backend-class`, 25) WHERE `attach`.`backend-class` LIKE 'Friendica\\\Model\\\Storage\\\%' ESCAPE '|'")) {
		return Update::FAILED;
	};

	return Update::SUCCESS;
}

function update_1332()
{
	$condition = ["`is-default` IS NOT NULL"];
	$profiles = DBA::select('profile', [], $condition);

	while ($profile = DBA::fetch($profiles)) {
		DI::profileField()->migrateFromLegacyProfile($profile);
	}
	DBA::close($profiles);

	DBA::update('contact', ['profile-id' => null], ['`profile-id` IS NOT NULL']);

	return Update::SUCCESS;
}

function update_1347()
{
	foreach (Item::ACTIVITIES as $index => $activity) {
		DBA::insert('verb', ['id' => $index + 1, 'name' => $activity], Database::INSERT_IGNORE);
	}

	return Update::SUCCESS;
}

function pre_update_1348()
{
	if (!DBA::exists('contact', ['id' => 0])) {
		DBA::insert('contact', ['nurl' => '']);
		$lastid = DBA::lastInsertId();
		if ($lastid != 0) {
			DBA::update('contact', ['id' => 0], ['id' => $lastid]);
		}
	}

	// The tables "permissionset" and "tag" could or could not exist during the update.
	// This depends upon the previous version. Depending upon this situation we have to add
	// the "0" values before adding the foreign keys - or after would be sufficient.

	update_1348();

	DBA::e("DELETE FROM `auth_codes` WHERE NOT `client_id` IN (SELECT `client_id` FROM `clients`)");
	DBA::e("DELETE FROM `tokens` WHERE NOT `client_id` IN (SELECT `client_id` FROM `clients`)");

	return Update::SUCCESS;
}

function update_1348()
{
	// Insert a permissionset with id=0
	// Inserting it without an ID and then changing the value to 0 tricks the auto increment
	if (!DBA::exists('permissionset', ['id' => 0])) {
		DBA::insert('permissionset', ['allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '']);	
		$lastid = DBA::lastInsertId();
		if ($lastid != 0) {
			DBA::update('permissionset', ['id' => 0], ['id' => $lastid]);
		}
	}

	if (!DBA::exists('tag', ['id' => 0])) {
		DBA::insert('tag', ['name' => '']);
		$lastid = DBA::lastInsertId();
		if ($lastid != 0) {
			DBA::update('tag', ['id' => 0], ['id' => $lastid]);
		}
	}

	return Update::SUCCESS;
}

function update_1349()
{
	$correct = true;
	foreach (Item::ACTIVITIES as $index => $activity) {
		if (!DBA::exists('verb', ['id' => $index + 1, 'name' => $activity])) {
			$correct = false;
		}
	}

	if (!$correct) {
		// The update failed - but it cannot be recovered, since the data doesn't match our expectation
		// This means that we can't use this "shortcut" to fill the "vid" field and we have to rely upon
		// the postupdate. This is not fatal, but means that it will take some longer time for the system
		// to fill all data.
		return Update::SUCCESS;
	}

	if (!DBA::e("UPDATE `item` INNER JOIN `item-activity` ON `item`.`uri-id` = `item-activity`.`uri-id`
		SET `vid` = `item-activity`.`activity` + 1 WHERE `gravity` = ? AND (`vid` IS NULL OR `vid` = 0)", GRAVITY_ACTIVITY)) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1351()
{
	if (!DBA::e("UPDATE `thread` INNER JOIN `item` ON `thread`.`iid` = `item`.`id` SET `thread`.`uri-id` = `item`.`uri-id`")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1354()
{
	if (DBStructure::existsColumn('contact', ['ffi_keyword_blacklist'])
		&& !DBStructure::existsColumn('contact', ['ffi_keyword_denylist'])
		&& !DBA::e("ALTER TABLE `contact` CHANGE `ffi_keyword_blacklist` `ffi_keyword_denylist` text null")) {
		return Update::FAILED;
	}
	return Update::SUCCESS;
}

function update_1354()
{
	if (DBStructure::existsColumn('contact', ['ffi_keyword_blacklist'])
		&& DBStructure::existsColumn('contact', ['ffi_keyword_denylist'])) {
		if (!DBA::e("UPDATE `contact` SET `ffi_keyword_denylist` = `ffi_keyword_blacklist`")) {
			return Update::FAILED;
		}

		// When the data had been copied then the main task is done.
		// Having the old field removed is only beauty but not crucial.
		// So we don't care if this was successful or not.
		DBA::e("ALTER TABLE `contact` DROP `ffi_keyword_blacklist`");
	}
	return Update::SUCCESS;
}

function update_1357()
{
	if (!DBA::e("UPDATE `contact` SET `failed` = true WHERE `success_update` < `failure_update` AND `failed` IS NULL")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `contact` SET `failed` = false WHERE `success_update` > `failure_update` AND `failed` IS NULL")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `contact` SET `failed` = false WHERE `updated` > `failure_update` AND `failed` IS NULL")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `contact` SET `failed` = false WHERE `last-item` > `failure_update` AND `failed` IS NULL")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `gserver` SET `failed` = true WHERE `last_contact` < `last_failure` AND `failed` IS NULL")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `gserver` SET `failed` = false WHERE `last_contact` > `last_failure` AND `failed` IS NULL")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1358()
{
	if (!DBA::e("DELETE FROM `contact-relation` WHERE NOT `relation-cid` IN (SELECT `id` FROM `contact`) OR NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1363()
{
	Photo::delete(["`contact-id` != ? AND NOT `contact-id` IN (SELECT `id` FROM `contact`)", 0]);
	return Update::SUCCESS;
}

function pre_update_1364()
{
	if (!DBA::e("DELETE FROM `2fa_recovery_codes` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `2fa_app_specific_password` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `attach` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `clients` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `conv` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `fsuggest` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `group` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `intro` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `manage` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `manage` WHERE NOT `mid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `mail` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `mailacct` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `notify` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `openwebauth-token` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `pconfig` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `profile` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `profile_check` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `profile_field` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `push_subscriber` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `register` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `search` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `tokens` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `user-contact` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `user-item` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `notify-threads` WHERE NOT `receiver-uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `event` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `fsuggest` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `group_member` WHERE NOT `contact-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `intro` WHERE NOT `contact-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `participation` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `profile_check` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `user-contact` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `participation` WHERE NOT `fid` IN (SELECT `id` FROM `fcontact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `group_member` WHERE NOT `gid` IN (SELECT `id` FROM `group`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `gserver-tag` WHERE NOT `gserver-id` IN (SELECT `id` FROM `gserver`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `participation` WHERE NOT `iid` IN (SELECT `id` FROM `item`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `user-item` WHERE NOT `iid` IN (SELECT `id` FROM `item`)")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1365()
{
	if (!DBA::e("DELETE FROM `notify-threads` WHERE NOT `notify-id` IN (SELECT `id` FROM `notify`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `thread` WHERE NOT `iid` IN (SELECT `id` FROM `item`)")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1375()
{
	if (!DBA::e("UPDATE `item` SET `thr-parent` = `parent-uri`, `thr-parent-id` = `parent-uri-id` WHERE `thr-parent` = ''")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1376()
{
	// Insert a user with uid=0
	DBStructure::checkInitialValues();

	if (!DBA::e("DELETE FROM `item` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `event` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `thread` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `permissionset` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `openwebauth-token` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `post-category` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	Photo::delete(["NOT `uid` IN (SELECT `uid` FROM `user`)"]);

	if (!DBA::e("DELETE FROM `contact` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1377()
{
	DBStructure::checkInitialValues();

	if (!DBA::e("DELETE FROM `item` WHERE NOT `author-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `item` WHERE NOT `owner-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `item` SET `contact-id` = `owner-id` WHERE NOT `contact-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `thread` WHERE NOT `author-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `thread` WHERE NOT `owner-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `thread` SET `contact-id` = `owner-id` WHERE NOT `contact-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `notify` SET `uri-id` = NULL WHERE `uri-id` = 0")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('diaspora-interaction') && !DBA::e("DELETE FROM `diaspora-interaction` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('item-activity') && !DBA::e("DELETE FROM `item-activity` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('item-content') && !DBA::e("DELETE FROM `item-content` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `notify` WHERE `uri-id` NOT IN (SELECT `id` FROM `item-uri`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `notify` SET `parent-uri-id` = NULL WHERE `parent-uri-id` = 0")) {
		return Update::FAILED;
	}
	if (!DBA::e("DELETE FROM `notify` WHERE `parent-uri-id` NOT IN (SELECT `id` FROM `item-uri`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `notify-threads` SET `master-parent-uri-id` = NULL WHERE `master-parent-uri-id` = 0")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `notify-threads` WHERE `master-parent-uri-id` NOT IN (SELECT `id` FROM `item-uri`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `notify-threads` WHERE `master-parent-item` NOT IN (SELECT `id` FROM `item`)")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1380()
{
	if (!DBA::e("UPDATE `notify` INNER JOIN `item` ON `item`.`id` = `notify`.`iid` SET `notify`.`uri-id` = `item`.`uri-id` WHERE `notify`.`uri-id` IS NULL AND `notify`.`otype` IN (?, ?)",
		Notification\ObjectType::ITEM, Notification\ObjectType::PERSON)) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `notify` INNER JOIN `item` ON `item`.`id` = `notify`.`parent` SET `notify`.`parent-uri-id` = `item`.`uri-id` WHERE `notify`.`parent-uri-id` IS NULL AND `notify`.`otype` IN (?, ?)",
		Notification\ObjectType::ITEM, Notification\ObjectType::PERSON)) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}
