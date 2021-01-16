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

namespace Friendica\Module\Update;

use Friendica\BaseModule;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\DateTimeFormat;

class Profile extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if (DI::config()->get('system', 'block_public') && !local_user() && !Session::getRemoteContactID($a->profile['uid'])) {
			throw new ForbiddenException();
		}

		$profile_uid = intval($_GET['p'] ?? 0);

		// Ensure we've got a profile owner if updating.
		$a->profile['uid'] = $profile_uid;

		$remote_contact = Session::getRemoteContactID($a->profile['uid']);
		$is_owner = local_user() == $a->profile['uid'];
		$last_updated_key = "profile:" . $a->profile['uid'] . ":" . local_user() . ":" . $remote_contact;

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact) {
			throw new ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
		}

		$o = '';

		if (empty($_GET['force']) && DI::pConfig()->get(local_user(), 'system', 'no_auto_update')) {
			System::htmlUpdateExit($o);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$sql_extra = Item::getPermissionsSQLByUserId($a->profile['uid']);

		$last_updated_array = Session::get('last_updated', []);

		$last_updated = $last_updated_array[$last_updated_key] ?? 0;

		// If the page user is the owner of the page we should query for unseen
		// items. Otherwise use a timestamp of the last succesful update request.
		if ($is_owner || !$last_updated) {
			$sql_extra4 = " AND `item`.`unseen`";
		} else {
			$gmupdate = gmdate(DateTimeFormat::MYSQL, $last_updated);
			$sql_extra4 = " AND `item`.`received` > '" . $gmupdate . "'";
		}

		$items_stmt = DBA::p(
			"SELECT DISTINCT(`parent-uri`) AS `uri`, `item`.`created`
			FROM `item`
			INNER JOIN `contact`
			ON `contact`.`id` = `item`.`contact-id`
				AND NOT `contact`.`blocked`
				AND NOT `contact`.`pending`
			WHERE `item`.`uid` = ?
				AND `item`.`visible`
				AND	(NOT `item`.`deleted` OR `item`.`gravity` = ?)
				AND NOT `item`.`moderated`
				AND `item`.`wall`
				$sql_extra4
				$sql_extra
			ORDER BY `item`.`received` DESC",
			$a->profile['uid'],
			GRAVITY_ACTIVITY
		);

		if (!DBA::isResult($items_stmt)) {
			return '';
		}

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		Session::set('last_updated', $last_updated_array);

		if ($is_owner && !$profile_uid && !DI::config()->get('theme', 'hide_eventlist')) {
			$o .= ProfileModel::getBirthdays();
			$o .= ProfileModel::getEventsReminderHTML();
		}

		if ($is_owner) {
			$unseen = Post::exists(['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			}
		}

		$items = DBA::toArray($items_stmt);

		$o .= conversation($a, $items, 'profile', $profile_uid, false, 'received', $a->profile['uid']);

		System::htmlUpdateExit($o);
	}
}
