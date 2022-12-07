<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
 * Automatic post-database structure change updates
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

use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Core\Storage\Type\Database as DatabaseStorage;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Notification;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Security\PermissionSet\Repository\PermissionSet;
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
					if (file_exists('view/lang/$lang/strings.php')) {
						include 'view/lang/$lang/strings.php';
					}
					if (file_exists('addon/morechoice/lang/$lang/strings.php')) {
						include 'addon/morechoice/lang/$lang/strings.php';
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

					Contact::updateSelfFromUserID($data['id']);
					Profile::publishUpdate($data['id']);
					$success++;
				}
			}
		}

		Logger::notice($translateKey . ' fix completed', ['action' => 'update', 'translateKey' => $translateKey, 'Success' => $success, 'Fail' => $fail ]);
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

		$deliver_options = ['priority' => Worker::PRIORITY_MEDIUM, 'dont_fork' => true];
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
	DBA::update('profile', ['marital' => 'In a relation'], ['marital' => 'Unavailable']);
	DBA::update('profile', ['marital' => 'Single'], ['marital' => 'Available']);

	Worker::add(Worker::PRIORITY_LOW, 'ProfileUpdate');
	return Update::SUCCESS;
}

function update_1323()
{
	$users = DBA::select('user', ['uid']);
	while ($user = DBA::fetch($users)) {
		if (Contact::updateSelfFromUserID($user['uid'])) {
			Profile::publishUpdate($user['uid']);
		}
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
		/** @var ICanReadFromStorage $currStorage */
		if (!DI::config()->set('storage', 'name', $currStorage::getName())) {
			return Update::FAILED;
		}

		// try to delete the class since it isn't needed. This won't work with config files
		DI::config()->delete('storage', 'class');
	}

	// Update attachments and photos
	if (!DBA::e("UPDATE `photo` SET `photo`.`backend-class` = SUBSTR(`photo`.`backend-class`, 25) WHERE `photo`.`backend-class` LIKE 'Friendica\\\Model\\\Storage\\\%' ESCAPE '|'") ||
	    !DBA::e("UPDATE `attach` SET `attach`.`backend-class` = SUBSTR(`attach`.`backend-class`, 25) WHERE `attach`.`backend-class` LIKE 'Friendica\\\Model\\\Storage\\\%' ESCAPE '|'")) {
		return Update::FAILED;
	};

	return Update::SUCCESS;
}

function update_1332()
{
	$condition = ["`is-default` IS NOT NULL"];
	$profiles = DBA::select('profile', [], $condition);

	while ($profile = DBA::fetch($profiles)) {
		Profile::migrate($profile);
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

	if (DBStructure::existsTable('auth_codes') && DBStructure::existsTable('clients')) {
		DBA::e("DELETE FROM `auth_codes` WHERE NOT `client_id` IN (SELECT `client_id` FROM `clients`)");
	}
	if (DBStructure::existsTable('tokens') && DBStructure::existsTable('clients')) {
		DBA::e("DELETE FROM `tokens` WHERE NOT `client_id` IN (SELECT `client_id` FROM `clients`)");
	}
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
	if (!DBStructure::existsTable('item-activity')) {
		return Update::SUCCESS;
	}

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
		SET `vid` = `item-activity`.`activity` + 1 WHERE `gravity` = ? AND (`vid` IS NULL OR `vid` = 0)", Item::GRAVITY_ACTIVITY)) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1351()
{
	if (DBStructure::existsTable('thread') && !DBA::e("UPDATE `thread` INNER JOIN `item` ON `thread`.`iid` = `item`.`id` SET `thread`.`uri-id` = `item`.`uri-id`")) {
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

	if (DBStructure::existsTable('clients') && !DBA::e("DELETE FROM `clients` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
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

	if (DBStructure::existsTable('profile_check') && !DBA::e("DELETE FROM `profile_check` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
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

	if (DBStructure::existsTable('tokens') && !DBA::e("DELETE FROM `tokens` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `user-contact` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('user-item') && !DBA::e("DELETE FROM `user-item` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
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

	if (DBStructure::existsTable('profile_check') && !DBA::e("DELETE FROM `profile_check` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `user-contact` WHERE NOT `cid` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `group_member` WHERE NOT `gid` IN (SELECT `id` FROM `group`)")) {
		return Update::FAILED;
	}

	if (!DBA::e("DELETE FROM `gserver-tag` WHERE NOT `gserver-id` IN (SELECT `id` FROM `gserver`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('user-item') && !DBA::e("DELETE FROM `user-item` WHERE NOT `iid` IN (SELECT `id` FROM `item`)")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1365()
{
	if (!DBA::e("DELETE FROM `notify-threads` WHERE NOT `notify-id` IN (SELECT `id` FROM `notify`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('thread') && !DBA::e("DELETE FROM `thread` WHERE NOT `iid` IN (SELECT `id` FROM `item`)")) {
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

	if (DBStructure::existsTable('thread') && !DBA::e("DELETE FROM `thread` WHERE NOT `uid` IN (SELECT `uid` FROM `user`)")) {
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

	if (DBStructure::existsTable('thread') && !DBA::e("DELETE FROM `thread` WHERE NOT `author-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('thread') && !DBA::e("DELETE FROM `thread` WHERE NOT `owner-id` IN (SELECT `id` FROM `contact`)")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('thread') && !DBA::e("UPDATE `thread` SET `contact-id` = `owner-id` WHERE NOT `contact-id` IN (SELECT `id` FROM `contact`)")) {
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

function pre_update_1395()
{
	if (DBStructure::existsTable('post-user') && !DBStructure::existsColumn('post-user', ['id']) && !DBA::e("DROP TABLE `post-user`")) {
		return Update::FAILED;
	}
	return Update::SUCCESS;
}

function update_1395()
{
	if (!DBA::e("INSERT INTO `post-user`(`id`, `uri-id`, `uid`, `contact-id`, `unseen`, `origin`, `psid`)
		SELECT `id`, `uri-id`, `uid`, `contact-id`, `unseen`, `origin`, `psid` FROM `item`
		ON DUPLICATE KEY UPDATE `contact-id` = `item`.`contact-id`, `unseen` = `item`.`unseen`, `origin` = `item`.`origin`, `psid` = `item`.`psid`")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('user-item') && !DBA::e("INSERT INTO `post-user`(`uri-id`, `uid`, `hidden`, `notification-type`)
		SELECT `uri-id`, `user-item`.`uid`, `hidden`,`notification-type` FROM `user-item`
			INNER JOIN `item` ON `item`.`id` = `user-item`.`iid`
		ON DUPLICATE KEY UPDATE `hidden` = `user-item`.`hidden`, `notification-type` = `user-item`.`notification-type`")) {
		return Update::FAILED;
	}
	return Update::SUCCESS;
}

function update_1396()
{
	if (!DBStructure::existsTable('item-content')) {
		return Update::SUCCESS;
	}

	if (DBStructure::existsColumn('item-content', ['raw-body'])) {
		if (!DBA::e("INSERT IGNORE INTO `post-content`(`uri-id`, `title`, `content-warning`, `body`, `raw-body`,
			`location`, `coord`, `language`, `app`, `rendered-hash`, `rendered-html`,
			`object-type`, `object`, `target-type`, `target`, `resource-id`, `plink`)
			SELECT `item-content`.`uri-id`, `item-content`.`title`, `item-content`.`content-warning`,
				`item-content`.`body`, `item-content`.`raw-body`, `item-content`.`location`, `item-content`.`coord`,
				`item-content`.`language`, `item-content`.`app`, `item-content`.`rendered-hash`,
				`item-content`.`rendered-html`, `item-content`.`object-type`, `item-content`.`object`,
				`item-content`.`target-type`, `item-content`.`target`, `item`.`resource-id`, `item-content`.`plink`
				FROM `item-content` INNER JOIN `item` ON `item`.`uri-id` = `item-content`.`uri-id`")) {
			return Update::FAILED;
		}
	} else {
		if (!DBA::e("INSERT IGNORE INTO `post-content`(`uri-id`, `title`, `content-warning`, `body`,
			`location`, `coord`, `language`, `app`, `rendered-hash`, `rendered-html`,
			`object-type`, `object`, `target-type`, `target`, `resource-id`, `plink`)
			SELECT `item-content`.`uri-id`, `item-content`.`title`, `item-content`.`content-warning`,
				`item-content`.`body`, `item-content`.`location`, `item-content`.`coord`,
				`item-content`.`language`, `item-content`.`app`, `item-content`.`rendered-hash`,
				`item-content`.`rendered-html`, `item-content`.`object-type`, `item-content`.`object`,
				`item-content`.`target-type`, `item-content`.`target`, `item`.`resource-id`, `item-content`.`plink`
				FROM `item-content` INNER JOIN `item` ON `item`.`uri-id` = `item-content`.`uri-id`")) {
			return Update::FAILED;
		}
	}
	return Update::SUCCESS;
}

function update_1397()
{
	if (!DBA::e("INSERT INTO `post-user-notification`(`uri-id`, `uid`, `notification-type`)
		SELECT `uri-id`, `uid`, `notification-type` FROM `post-user` WHERE `notification-type` != 0
		ON DUPLICATE KEY UPDATE `uri-id` = `post-user`.`uri-id`, `uid` = `post-user`.`uid`, `notification-type` = `post-user`.`notification-type`")) {
		return Update::FAILED;
	}

	if (!DBStructure::existsTable('user-item')) {
		return Update::SUCCESS;
	}

	if (!DBA::e("INSERT INTO `post-user-notification`(`uri-id`, `uid`, `notification-type`)
		SELECT `uri-id`, `user-item`.`uid`, `notification-type` FROM `user-item`
			INNER JOIN `item` ON `item`.`id` = `user-item`.`iid` WHERE `notification-type` != 0
		ON DUPLICATE KEY UPDATE `notification-type` = `user-item`.`notification-type`")) {
		return Update::FAILED;
	}

	if (!DBStructure::existsTable('thread')) {
		return Update::SUCCESS;
	}

	if (!DBA::e("INSERT IGNORE INTO `post-thread-user`(`uri-id`, `uid`, `pinned`, `starred`, `ignored`, `wall`, `pubmail`, `forum_mode`)
		SELECT `thread`.`uri-id`, `thread`.`uid`, `user-item`.`pinned`, `thread`.`starred`,
			`thread`.`ignored`, `thread`.`wall`, `thread`.`pubmail`, `thread`.`forum_mode`
		FROM `thread` LEFT JOIN `user-item` ON `user-item`.`iid` = `thread`.`iid`")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1398()
{
	if (!DBStructure::existsTable('thread')) {
		return Update::SUCCESS;
	}

	if (!DBA::e("INSERT IGNORE INTO `post-thread` (`uri-id`, `owner-id`, `author-id`, `network`, `created`, `received`, `changed`, `commented`)
		SELECT `uri-id`, `owner-id`, `author-id`, `network`, `created`, `received`, `changed`, `commented` FROM `thread`")) {
			return Update::FAILED;
	}

	if (!DBStructure::existsTable('thread')) {
		return Update::SUCCESS;
	}

	if (!DBA::e("UPDATE `post-thread-user` INNER JOIN `thread` ON `thread`.`uid` = `post-thread-user`.`uid` AND `thread`.`uri-id` = `post-thread-user`.`uri-id`
		SET `post-thread-user`.`mention` = `thread`.`mention`")) {
			return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1399()
{
	if (!DBA::e("UPDATE `post-thread-user` INNER JOIN `post-user` ON `post-user`.`uid` = `post-thread-user`.`uid` AND `post-user`.`uri-id` = `post-thread-user`.`uri-id`
		SET `post-thread-user`.`contact-id` = `post-user`.`contact-id`, `post-thread-user`.`unseen` = `post-user`.`unseen`,
		`post-thread-user`.`hidden` = `post-user`.`hidden`, `post-thread-user`.`origin` = `post-user`.`origin`,
		`post-thread-user`.`psid` = `post-user`.`psid`, `post-thread-user`.`post-user-id` = `post-user`.`id`")) {
			return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1400()
{
	if (!DBA::e("INSERT IGNORE INTO `post` (`uri-id`, `parent-uri-id`, `thr-parent-id`, `owner-id`, `author-id`, `network`,
		`created`, `received`, `edited`, `gravity`, `causer-id`, `post-type`, `vid`, `private`, `visible`, `deleted`, `global`)
		SELECT `uri-id`, `parent-uri-id`, `thr-parent-id`, `owner-id`, `author-id`, `network`, `created`, `received`, `edited`,
			`gravity`, `causer-id`, `post-type`, `vid`, `private`, `visible`, `deleted`, `global` FROM `item`")) {
			return Update::FAILED;
	}

	if (!DBA::e("UPDATE `post-user` INNER JOIN `item` ON `item`.`uri-id` = `post-user`.`uri-id` AND `item`.`uid` = `post-user`.`uid`
		INNER JOIN `event` ON `item`.`event-id` = `event`.`id` AND `event`.`id` != 0
		SET `post-user`.`event-id` = `item`.`event-id`")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `post-user` INNER JOIN `item` ON `item`.`uri-id` = `post-user`.`uri-id` AND `item`.`uid` = `post-user`.`uid`
		SET `post-user`.`wall` = `item`.`wall`, `post-user`.`parent-uri-id` = `item`.`parent-uri-id`,
		`post-user`.`thr-parent-id` = `item`.`thr-parent-id`,
		`post-user`.`created` = `item`.`created`, `post-user`.`edited` = `item`.`edited`,
		`post-user`.`received` = `item`.`received`, `post-user`.`gravity` = `item`.`gravity`,
		`post-user`.`network` = `item`.`network`, `post-user`.`owner-id` = `item`.`owner-id`,
		`post-user`.`author-id` = `item`.`author-id`, `post-user`.`causer-id` = `item`.`causer-id`,
		`post-user`.`post-type` = `item`.`post-type`, `post-user`.`vid` = `item`.`vid`,
		`post-user`.`private` = `item`.`private`, `post-user`.`global` = `item`.`global`,
		`post-user`.`visible` = `item`.`visible`, `post-user`.`deleted` = `item`.`deleted`")) {
		return Update::FAILED;
	}

	if (!DBA::e("INSERT IGNORE INTO `post-thread-user` (`uri-id`, `owner-id`, `author-id`, `causer-id`, `network`,
		`created`, `received`, `changed`, `commented`, `uid`,  `wall`, `contact-id`, `unseen`, `hidden`, `origin`, `psid`, `post-user-id`)
		SELECT `uri-id`, `owner-id`, `author-id`, `causer-id`, `network`, `created`, `received`, `received`, `received`,
			`uid`, `wall`, `contact-id`, `unseen`, `hidden`, `origin`, `psid`, `id`
		FROM `post-user` WHERE `gravity` = 0 AND NOT EXISTS(SELECT `uri-id` FROM `post-thread-user` WHERE `post-user-id` = `post-user`.id)")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `post-thread-user` INNER JOIN `post-thread` ON `post-thread-user`.`uri-id` = `post-thread`.`uri-id`
		SET `post-thread-user`.`owner-id` = `post-thread`.`owner-id`, `post-thread-user`.`author-id` = `post-thread`.`author-id`,
		`post-thread-user`.`causer-id` = `post-thread`.`causer-id`, `post-thread-user`.`network` = `post-thread`.`network`,
		`post-thread-user`.`created` = `post-thread`.`created`, `post-thread-user`.`received` = `post-thread`.`received`,
		`post-thread-user`.`changed` = `post-thread`.`changed`, `post-thread-user`.`commented` = `post-thread`.`commented`")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function pre_update_1403()
{
	// Necessary before a primary key change
	if (DBStructure::existsTable('parsed_url') && !DBA::e("DROP TABLE `parsed_url`")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1404()
{
	$tasks = DBA::select('workerqueue', ['id', 'command', 'parameter'], ['command' => ['notifier', 'delivery', 'apdelivery', 'done' => false]]);
	while ($task = DBA::fetch($tasks)) {
		$parameters = json_decode($task['parameter'], true);

		if (is_array($parameters) && count($parameters) && in_array($parameters[0], [Delivery::MAIL, Delivery::SUGGESTION, Delivery::REMOVAL, Delivery::RELOCATION])) {
			continue;
		}

		switch (strtolower($task['command'])) {
			case 'notifier':
				if (count($parameters) == 3) {
					continue 2;
				}
				$item = DBA::selectFirst('item', ['uid', 'uri-id'], ['id' => $parameters[1]]);
				if (!DBA::isResult($item)) {
					continue 2;
				}

				$parameters[1] = $item['uri-id'];
				$parameters[2] = $item['uid'];
				break;
			case 'delivery':
				if (count($parameters) == 4) {
					continue 2;
				}
				$item = DBA::selectFirst('item', ['uid', 'uri-id'], ['id' => $parameters[1]]);
				if (!DBA::isResult($item)) {
					continue 2;
				}

				$parameters[1] = $item['uri-id'];
				$parameters[3] = $item['uid'];
				break;
			case 'apdelivery':
				if (count($parameters) == 6) {
					continue 2;
				}

				if (empty($parameters[4])) {
					$parameters[4] = [];
				}

				$item = DBA::selectFirst('item', ['uri-id'], ['id' => $parameters[1]]);
				if (!DBA::isResult($item)) {
					continue 2;
				}

				$parameters[5] = $item['uri-id'];
				break;
			default:
				continue 2;
		}
		DBA::update('workerqueue', ['parameter' => json_encode($parameters)], ['id' => $task['id']]);

		return Update::SUCCESS;
	}
}

function update_1407()
{
	if (!DBA::e("UPDATE `post` SET `causer-id` = NULL WHERE `causer-id` = 0")) {
		return Update::FAILED;
	}
	if (!DBA::e("UPDATE `post-user` SET `causer-id` = NULL WHERE `causer-id` = 0")) {
		return Update::FAILED;
	}
	if (!DBA::e("UPDATE `post-thread` SET `causer-id` = NULL WHERE `causer-id` = 0")) {
		return Update::FAILED;
	}
	if (!DBA::e("UPDATE `post-thread-user` SET `causer-id` = NULL WHERE `causer-id` = 0")) {
		return Update::FAILED;
	}

	return Update::SUCCESS;
}

function update_1413()
{
	if (!DBA::e("UPDATE `post-user` SET `post-reason` = `post-type` WHERE `post-type` >= 64 and `post-type` <= 75")) {
		return Update::FAILED;
	}
}

function update_1419()
{
	$mails = DBA::select('mail', ['id', 'from-url', 'uri', 'parent-uri', 'guid'], [], ['order' => ['id']]);
	while ($mail = DBA::fetch($mails)) {
		$fields = [];
		$fields['author-id'] = Contact::getIdForURL($mail['from-url'], 0, false);
		if (empty($fields['author-id'])) {
			continue;
		}

		$fields['uri-id']        = ItemURI::insert(['uri' => $mail['uri'], 'guid' => $mail['guid']]);
		$fields['parent-uri-id'] = ItemURI::getIdByURI($mail['parent-uri']);

		$reply = DBA::selectFirst('mail', ['uri', 'uri-id', 'guid'], ['parent-uri' => $mail['parent-uri'], 'reply' => false]);
		if (!empty($reply)) {
			$fields['thr-parent'] = $reply['uri'];
			if (!empty($reply['uri-id'])) {
				$fields['thr-parent-id'] = $reply['uri-id'];
			} else {
				$fields['thr-parent-id'] = ItemURI::insert(['uri' => $reply['uri'], 'guid' => $reply['guid']]);
			}
		}

		DBA::update('mail', $fields, ['id' => $mail['id']]);
	}
	return Update::SUCCESS;
}

function update_1429()
{
	if (!DBA::e("UPDATE `contact` SET `uri-id` = null WHERE NOT `uri-id` IS NULL")) {
		return Update::FAILED;
	}

	if (DBStructure::existsTable('fcontact') && !DBA::e("UPDATE `fcontact` SET `uri-id` = null WHERE NOT `uri-id` IS NULL")) {
		return Update::FAILED;
	}

	if (!DBA::e("UPDATE `apcontact` SET `uri-id` = null WHERE NOT `uri-id` IS NULL")) {
		return Update::FAILED;
	}

	DI::config()->set('system', 'post_update_version', 1423);

	return Update::SUCCESS;
}

function update_1434()
{
	$name = DI::config()->get('storage', 'name');

	// In case of an empty config, set "Database" as default storage backend
	if (empty($name)) {
		DI::config()->set('storage', 'name', DatabaseStorage::getName());
	}

	// In case of a Using deprecated storage class value, set the right name for it
	if (stristr($name, 'Friendica\Model\Storage\\')) {
		DI::config()->set('storage', 'name', substr($name, 24));
	}

	return Update::SUCCESS;
}

function update_1438()
{
	DBA::update('photo', ['photo-type' => Photo::USER_AVATAR], ['profile' => true]);
	DBA::update('photo', ['photo-type' => Photo::CONTACT_AVATAR], ["NOT `profile` AND NOT `contact-id` IS NULL AND `contact-id` != ?", 0]);
	DBA::update('photo', ['photo-type' => Photo::DEFAULT], ["NOT `profile` AND (`contact-id` IS NULL OR `contact-id` = ?) AND `photo-type` IS NULL AND `album` != ?", 0, Photo::CONTACT_PHOTOS]);
}

function update_1439()
{
	if (!DBStructure::existsTable('fcontact')) {
		return Update::SUCCESS;
	}

	$intros = DBA::select('intro', ['id', 'fid'], ["NOT `fid` IS NULL AND `fid` != ?", 0]);
	while ($intro = DBA::fetch($intros)) {
		$fcontact = DBA::selectFirst('fcontact', ['url'], ['id' => $intro['fid']]);
		if (!empty($fcontact['url'])) {
			$id = Contact::getIdForURL($fcontact['url']);
			if (!empty($id)) {
				DBA::update('intro',['suggest-cid' => $id], ['id' => $intro['id']]);
			}
		}
	}
	DBA::close($intros);

	return Update::SUCCESS;
}

function update_1440()
{
	// Fix wrong public permissionset
	DBA::p("UPDATE `profile_field` SET `psid` = ? WHERE psid IN (SELECT `id` FROM `permissionset` WHERE `id` != ? AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = '')", PermissionSet::PUBLIC, PermissionSet::PUBLIC);
	DBA::delete('permissionset', ["`id` != ? AND `allow_cid` = '' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = ''", PermissionSet::PUBLIC]);

	return Update::SUCCESS;
}

function update_1441()
{
	$languages = DI::l10n()->getAvailableLanguages();

	$albums = [Photo::PROFILE_PHOTOS];
	foreach ($languages as $language) {
		$albums[] = DI::l10n()->withLang($language)->t(Photo::PROFILE_PHOTOS);
	}
	$albums = array_unique($albums);

	Photo::update(['photo-type' => Photo::USER_AVATAR], ['album' => $albums]);

	return Update::SUCCESS;
}

function update_1442()
{
	// transform blocked intros into ignored intros
	DBA::update('intro', ['ignore' => 1, 'blocked' => 0], ['blocked' => 1]);

	return Update::SUCCESS;
}

/**
 * A bug in Contact\User::updateByContactUpdate prevented any update to the user-contact table since the rows have been
 * created in version 1435. This version fixes this bug but the user-contact rows are outdated, we need to regenerate
 * them.
 */
function update_1444()
{
	DBA::e('TRUNCATE TABLE `user-contact`');

	$contacts = DBA::select('contact', [], ["`uid` != ?", 0]);
	while ($contact = DBA::fetch($contacts)) {
		Contact\User::insertForContactArray($contact);
	}

	return Update::SUCCESS;
}

function update_1446()
{
	$distributed_cache_driver_source = DI::config()->getCache()->getSource('system', 'distributed_cache_driver');
	$cache_driver_source = DI::config()->getCache()->getSource('system', 'cache_driver');

	// In case the distributed cache driver is the default value, but the current cache driver isn't default,
	// we assume that the distributed cache driver should be the same as the current cache driver
	if ($distributed_cache_driver_source === Cache::SOURCE_STATIC && $cache_driver_source > Cache::SOURCE_STATIC) {
		DI::config()->set('system', 'distributed_cache_driver', DI::config()->get('system', 'cache_driver'));
	}

	return Update::SUCCESS;
}

function update_1451()
{
	DBA::update('user', ['account-type' => User::ACCOUNT_TYPE_COMMUNITY], ['page-flags' => [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]]);
	DBA::update('contact', ['contact-type' => Contact::TYPE_COMMUNITY], ["`forum` OR `prv`"]);
	DBA::update('contact', ['manually-approve' => true], ['prv' => true]);

	return Update::SUCCESS;
}

function update_1457()
{
	$pinned = DBA::select('post-thread-user', ['uri-id', 'author-id'], ['pinned' => true]);
	while ($post = DBA::fetch($pinned)) {
		Post\Collection::add($post['uri-id'], Post\Collection::FEATURED, $post['author-id']);
	}
	DBA::close($pinned);

	return Update::SUCCESS;
}

function update_1480()
{
	DBA::update('contact', ['next-update' => DBA::NULL_DATETIME], ['network' => Protocol::FEDERATED]);
	DBA::update('post', ['deleted' => false], ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE NOT `deleted`)"]);
	return Update::SUCCESS;
}

function update_1481()
{
	DBA::e("UPDATE `post-collection` INNER JOIN `post` ON `post`.`uri-id` = `post-collection`.`uri-id` SET `post-collection`.`author-id` = `post`.`author-id` WHERE `post-collection`.`author-id` IS null");
	return Update::SUCCESS;
}

function update_1491()
{
	DBA::update('contact', ['remote_self' => Contact::MIRROR_OWN_POST], ['remote_self' => Contact::MIRROR_FORWARDED]);
	return Update::SUCCESS;
}

function update_1497()
{
	DBA::e("UPDATE `user` SET `last-activity` = DATE(`login_date`) WHERE `last-activity` IS NULL");
	return Update::SUCCESS;
}