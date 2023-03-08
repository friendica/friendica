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

namespace Friendica\Model\Contact;

use Exception;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\ItemURI;
use PDOException;

/**
 * This class provides information about user related contacts based on the "user-contact" table.
 */
class User
{
	/**
	 * Insert a user-contact for a given contact array
	 *
	 * @param array $contact
	 * @return void
	 */
	public static function insertForContactArray(array $contact)
	{
		if (empty($contact['uid'])) {
			// We don't create entries for the public user - by now
			return false;
		}

		if (empty($contact['uri-id']) && empty($contact['url'])) {
			Logger::info('Missing contact details', ['contact' => $contact, 'callstack' => System::callstack(20)]);
			return false;
		}

		if (empty($contact['uri-id'])) {
			$contact['uri-id'] = ItemURI::getIdByURI($contact['url']);
		}

		$pcontact = Contact::selectFirst(['id'], ['uri-id' => $contact['uri-id'], 'uid' => 0]);
		if (!empty($contact['uri-id']) && DBA::isResult($pcontact)) {
			$pcid = $pcontact['id'];
		} elseif (empty($contact['url']) || !($pcid = Contact::getIdForURL($contact['url'], 0, false))) {
			Logger::info('Public contact for user not found', ['uri-id' => $contact['uri-id'], 'uid' => $contact['uid']]);
			return false;
		}

		$fields = self::preparedFields($contact);
		$fields['cid'] = $pcid;
		$fields['uid'] = $contact['uid'];
		$fields['uri-id'] = $contact['uri-id'];

		$ret = DBA::insert('user-contact', $fields, Database::INSERT_UPDATE);

		Logger::info('Inserted user contact', ['uid' => $contact['uid'], 'cid' => $pcid, 'uri-id' => $contact['uri-id'], 'ret' => $ret]);

		return $ret;
	}

	/**
	 * Apply changes from contact update data to user-contact table
	 *
	 * @param array $fields
	 * @param array $condition
	 * @return void
	 * @throws PDOException
	 * @throws Exception
	 */
	public static function updateByContactUpdate(array $fields, array $condition)
	{
		DBA::transaction();

		$update_fields = self::preparedFields($fields);
		if (!empty($update_fields)) {
			$contacts = DBA::select('contact', ['uri-id', 'uid'], $condition);
			while ($contact = DBA::fetch($contacts)) {
				if (empty($contact['uri-id']) || empty($contact['uid'])) {
					continue;
				}
				$ret = DBA::update('user-contact', $update_fields, ['uri-id' => $contact['uri-id'], 'uid' => $contact['uid']]);
				Logger::info('Updated user contact', ['uid' => $contact['uid'], 'uri-id' => $contact['uri-id'], 'ret' => $ret]);
			}

			DBA::close($contacts);
		}

		DBA::commit();
	}

	/**
	 * Prepare field data for update/insert
	 *
	 * @param array $fields
	 * @return array prepared fields
	 */
	private static function preparedFields(array $fields): array
	{
		unset($fields['uid']);
		unset($fields['cid']);
		unset($fields['uri-id']);

		if (isset($fields['readonly'])) {
			$fields['ignored'] = $fields['readonly'];
		}

		if (!empty($fields['self'])) {
			$fields['rel'] = Contact::SELF;
		}

		return DI::dbaDefinition()->truncateFieldsForTable('user-contact', $fields);
	}

	/**
	 * Block contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $blocked Is the contact blocked or unblocked?
	 * @return void
	 * @throws \Exception
	 */
	public static function setBlocked(int $cid, int $uid, bool $blocked)
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$contact = Contact::getById($cdata['public']);
		if ($blocked) {
			Protocol::block($contact, $uid);
		} else {
			Protocol::unblock($contact, $uid);
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['blocked' => $blocked], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['blocked' => $blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "block" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 * @throws \Exception
	 */
	public static function isBlocked(int $cid, int $uid): bool
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return false;
		}

		$public_blocked = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['blocked'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_blocked = (bool) $public_contact['blocked'];
			}
		}

		$user_blocked = $public_blocked;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['blocked'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_blocked = (bool) $user_contact['blocked'];
			}
		}

		if ($user_blocked != $public_blocked) {
			DBA::update('user-contact', ['blocked' => $user_blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_blocked;
	}

	/**
	 * Ignore contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $ignored Is the contact ignored or unignored?
	 * @return void
	 * @throws \Exception
	 */
	public static function setIgnored(int $cid, int $uid, bool $ignored)
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['readonly' => $ignored], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['ignored' => $ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "ignore" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 * @return boolean is the contact id ignored for the given user?
	 * @throws \Exception
	 */
	public static function isIgnored(int $cid, int $uid): bool
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return false;
		}

		$public_ignored = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['ignored'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_ignored = (bool) $public_contact['ignored'];
			}
		}

		$user_ignored = $public_ignored;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['readonly'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_ignored = (bool) $user_contact['readonly'];
			}
		}

		if ($user_ignored != $public_ignored) {
			DBA::update('user-contact', ['ignored' => $user_ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_ignored;
	}

	/**
	 * Set "collapsed" for contact id and user id
	 *
	 * @param int     $cid       Either public contact id or user's contact id
	 * @param int     $uid       User ID
	 * @param boolean $collapsed are the contact's posts collapsed or uncollapsed?
	 * @return void
	 * @throws \Exception
	 */
	public static function setCollapsed(int $cid, int $uid, bool $collapsed)
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		DBA::update('user-contact', ['collapsed' => $collapsed], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "collapsed" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 * @return boolean is the contact id blocked for the given user?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isCollapsed(int $cid, int $uid): bool
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return false;
		}

		$collapsed = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['collapsed'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$collapsed = (bool) $public_contact['collapsed'];
			}
		}

		return $collapsed;
	}

	/**
	 * Set/Release that the user is blocked by the contact
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $blocked Is the user blocked or unblocked by the contact?
	 * @return void
	 * @throws \Exception
	 */
	public static function setIsBlocked(int $cid, int $uid, bool $blocked)
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		DBA::update('user-contact', ['is-blocked' => $blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns if the user is blocked by the contact
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 * @return boolean Is the user blocked or unblocked by the contact?
	 * @throws \Exception
	 */
	public static function isIsBlocked(int $cid, int $uid): bool
	{
		$cdata = Contact::getPublicAndUserContactID($cid, $uid);
		if (empty($cdata)) {
			return false;
		}

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['is-blocked'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				return $public_contact['is-blocked'];
			}
		}

		return false;
	}
}
