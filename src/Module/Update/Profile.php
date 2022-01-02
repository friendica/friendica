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
use Friendica\Model\User;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\DateTimeFormat;

class Profile extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$a = DI::app();

		// Ensure we've got a profile owner if updating.
		$a->setProfileOwner($_GET['p'] ?? 0);

		if (DI::config()->get('system', 'block_public') && !local_user() && !Session::getRemoteContactID($a->getProfileOwner())) {
			throw new ForbiddenException();
		}

		$remote_contact = Session::getRemoteContactID($a->getProfileOwner());
		$is_owner = local_user() == $a->getProfileOwner();
		$last_updated_key = "profile:" . $a->getProfileOwner() . ":" . local_user() . ":" . $remote_contact;

		if (!$is_owner && !$remote_contact) {
			$user = User::getById($a->getProfileOwner(), ['hidewall']);
			if ($user['hidewall']) {
				throw new ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
			}
		}

		$o = '';

		if (empty($_GET['force']) && DI::pConfig()->get(local_user(), 'system', 'no_auto_update')) {
			System::htmlUpdateExit($o);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$sql_extra = Item::getPermissionsSQLByUserId($a->getProfileOwner());

		$last_updated_array = Session::get('last_updated', []);

		$last_updated = $last_updated_array[$last_updated_key] ?? 0;

		if ($_GET['force'] && !empty($_GET['item'])) {
			// When the parent is provided, we only fetch this
			$sql_extra4 = " AND `parent` = " . intval($_GET['item']);
		} elseif ($is_owner || !$last_updated) {
			// If the page user is the owner of the page we should query for unseen
			// items. Otherwise use a timestamp of the last succesful update request.
			$sql_extra4 = " AND `unseen`";
		} else {
			$gmupdate = gmdate(DateTimeFormat::MYSQL, $last_updated);
			$sql_extra4 = " AND `received` > '" . $gmupdate . "'";
		}

		$items_stmt = DBA::p(
			"SELECT `parent-uri-id` AS `uri-id`, MAX(`created`), MAX(`received`) FROM `post-user-view`
				WHERE `uid` = ? AND NOT `contact-blocked` AND NOT `contact-pending`
				AND `visible` AND (NOT `deleted` OR `gravity` = ?)
				AND `wall` $sql_extra4 $sql_extra
			GROUP BY `parent-uri-id` ORDER BY `received` DESC",
			$a->getProfileOwner(),
			GRAVITY_ACTIVITY
		);

		if (!DBA::isResult($items_stmt)) {
			return;
		}

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		Session::set('last_updated', $last_updated_array);

		if ($is_owner && !$a->getProfileOwner() && !DI::config()->get('theme', 'hide_eventlist')) {
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

		$o .= DI::conversation()->create($items, 'profile', $a->getProfileOwner(), false, 'received', $a->getProfileOwner());

		System::htmlUpdateExit($o);
	}
}
