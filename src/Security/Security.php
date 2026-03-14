<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
			}

			$user = User::getById($owner);
			if (!$user || $user['blockwall']) {
				$verified = 1;
				return false;
			}

			$contact = Contact::getById($cid);
			if (!is_array($contact) || $contact['blocked'] || $contact['readonly'] || $contact['pending']) {
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
		$local_user     = DI::userSession()->getLocalUserId();
		$remote_contact = DI::userSession()->getRemoteContactID($owner_id);
		$acc_sql        = '';

		if ($accessible) {
			$acc_sql = ' OR `accessible`';
		}

		// Construct permissions: default permissions - anonymous user
		$sql = " AND (allow_cid = ''
			 AND allow_gid = ''
			 AND deny_cid  = ''
			 AND deny_gid  = ''" . $acc_sql . ") ";

		if ($local_user && $local_user == $owner_id) {
			// Profile owner - everything is visible
			$sql = '';
		} elseif ($remote_contact) {
			// Authenticated visitor. Load the circles the visitor belongs to.
			$circleIds = '<<>>'; // should be impossible to match

			foreach (Circle::getIdsByContactId($remote_contact) as $circleId) {
				$circleIds .= '|<' . $circleId . '>';
			}

			$sql = sprintf(
				" AND (NOT (CAST(deny_cid AS BINARY) REGEXP BINARY '<%d>' OR CAST(deny_gid AS BINARY) REGEXP BINARY '%s')
				  AND (CAST(allow_cid AS BINARY) REGEXP BINARY '<%d>' OR CAST(allow_gid AS BINARY) REGEXP BINARY '%s'
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
