<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Security;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Model\User;

/**
 * Secures that User is allow to do requests
 */
class Security
{
	public static function canWriteToUserWall($owner)
	{
		static $verified = 0;

		if (!DI::userSession()->isAuthenticated()) {
			return false;
		}

		$uid = DI::userSession()->getLocalUserId();
		if ($uid == $owner) {
			return true;
		}

		if (DI::userSession()->getLocalUserId() && ($owner == 0)) {
			return true;
		}

		if (!empty($cid = DI::userSession()->getRemoteContactID($owner))) {
			// use remembered decision and avoid a DB lookup for each and every display item
			// DO NOT use this function if there are going to be multiple owners
			// We have a contact-id for an authenticated remote user, this block determines if the contact
			// belongs to this page owner, and has the necessary permissions to post content

			if ($verified === 2) {
				return true;
			} elseif ($verified === 1) {
				return false;
			} else {
				$user = User::getById($owner);
				if (!$user || $user['blockwall']) {
					$verified = 1;
					return false;
				}

				$contact = Contact::getById($cid);
				if ($contact || $contact['blocked'] || $contact['readonly'] || $contact['pending']) {
					$verified = 1;
					return false;
				}

				if (in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]) || ($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
					$verified = 2;
					return true;
				} else {
					$verified = 1;
				}
			}
		}

		return false;
	}

	/**
	 * Create a permission string for an element based on the visitor
	 *
	 * @param integer $owner_id   User ID of the owner of the element
	 * @param boolean $accessible Should the element be accessible anyway?
	 * @return string SQL permissions
	 */
	public static function getPermissionsSQLByUserId(int $owner_id, bool $accessible = false)
	{
		$local_user = DI::userSession()->getLocalUserId();
		$remote_contact = DI::userSession()->getRemoteContactID($owner_id);
		$acc_sql = '';

		if ($accessible) {
			$acc_sql = ' OR `accessible`';
		}

		/*
		 * Construct permissions
		 *
		 * default permissions - anonymous user
		 */
		$sql = " AND (allow_cid = ''
			 AND allow_gid = ''
			 AND deny_cid  = ''
			 AND deny_gid  = ''" . $acc_sql . ") ";

		/*
		 * Profile owner - everything is visible
		 */
		if ($local_user && $local_user == $owner_id) {
			$sql = '';
		/*
		 * Authenticated visitor. Load the circles the visitor belongs to.
		 */
		} elseif ($remote_contact) {
			$circleIds = '<<>>'; // should be impossible to match

			foreach (Circle::getIdsByContactId($remote_contact) as $circleId) {
				$circleIds .= '|<' . intval($circleId) . '>';
			}

			$sql = sprintf(
				" AND (NOT (deny_cid REGEXP '<%d>' OR deny_gid REGEXP '%s')
				  AND (allow_cid REGEXP '<%d>' OR allow_gid REGEXP '%s'
				  OR (allow_cid = '' AND allow_gid = ''))" . $acc_sql . ") ",
				intval($remote_contact),
				DBA::escape($circleIds),
				intval($remote_contact),
				DBA::escape($circleIds)
			);
		}
		return $sql;
	}
}
