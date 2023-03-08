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

namespace Friendica\Core\Session\Capability;

/**
 * This interface handles UserSessions, which is directly extended from the global Session interface
 */
interface IHandleUserSessions extends IHandleSessions
{
	/**
	 * Returns the user id of locally logged-in user or false.
	 *
	 * @return int|bool user id or false
	 */
	public function getLocalUserId();

	/**
	 * Returns the user nickname of locally logged-in user.
	 *
	 * @return string|false User's nickname or false
	 */
	public function getLocalUserNickname();

	/**
	 * Returns the public contact id of logged-in user or false.
	 *
	 * @return int|bool public contact id or false
	 */
	public function getPublicContactId();

	/**
	 * Returns public contact id of authenticated site visitor or false
	 *
	 * @return int|bool visitor_id or false
	 */
	public function getRemoteUserId();

	/**
	 * Return the user contact ID of a visitor for the given user ID they are visiting
	 *
	 * @param int $uid User ID
	 *
	 * @return int
	 */
	public function getRemoteContactID(int $uid): int;

	/**
	 * Returns User ID for given contact ID of the visitor
	 *
	 * @param int $cid Contact ID
	 *
	 * @return int User ID for given contact ID of the visitor
	 */
	public function getUserIDForVisitorContactID(int $cid): int;

	/**
	 * Returns the account URL of the currently logged in user
	 *
	 * @return string
	 */
	public function getMyUrl(): string;

	/**
	 * Returns if the current visitor is authenticated
	 *
	 * @return bool "true" when visitor is either a local or remote user
	 */
	public function isAuthenticated(): bool;

	/**
	 * Check if current user has admin role.
	 *
	 * @return bool true if user is an admin
	 */
	public function isSiteAdmin(): bool;

	/**
	 * Returns User ID of the managed user in case it's a different identity
	 *
	 * @return int|bool uid of the manager or false
	 */
	public function getSubManagedUserId();

	/**
	 * Sets the User ID of the managed user in case it's a different identity
	 *
	 * @param int $managed_uid The user id of the managing user
	 */
	public function setSubManagedUserId(int $managed_uid): void;

	/**
	 * Set the session variable that contains the contact IDs for the visitor's contact URL
	 */
	public function setVisitorsContacts();
}
