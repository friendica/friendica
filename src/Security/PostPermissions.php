<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security;

use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Security\PermissionSet\Repository\PermissionSet;

/**
 * Creates the conditions that limit posts to those the current visitor is allowed to see
 */
final readonly class PostPermissions
{
	public function __construct(
		private IHandleUserSessions $userSession,
		private PermissionSet $permissionSet,
	) {}

	/**
	 * Fetch the SQL condition for the given user id
	 *
	 * @param integer $owner_id User ID for which the permissions should be fetched
	 * @return array condition
	 */
	public function getConditionArray(int $owner_id): array
	{
		$local_user  = $this->userSession->getLocalUserId();
		$remote_user = $this->userSession->getRemoteContactID($owner_id);

		// default permissions - anonymous user
		$condition = ["`private` != ?", Item::PRIVATE];

		if ($local_user && ($local_user == $owner_id)) {
			// Profile owner - everything is visible
			$condition = [];
		} elseif ($remote_user) {
			// Authenticated visitor - fetch the matching permissionsets
			$permissionSets = $this->permissionSet->selectByContactId($remote_user, $owner_id);
			if (count($permissionSets) > 0) {
				$condition = [
					"(`private` != ? OR (`private` = ? AND `wall`
					AND `psid` IN (" . implode(', ', array_fill(0, count($permissionSets), '?')) . ")))",
					Item::PRIVATE, Item::PRIVATE,
				];
				$condition = array_merge($condition, $permissionSets->column('id'));
			}
		}

		return $condition;
	}

	/**
	 * Get a permission SQL string for the given user
	 *
	 * @param int $owner_id
	 * @param string $table
	 * @return string
	 */
	public function getSQL(int $owner_id, string $table = ''): string
	{
		$local_user  = $this->userSession->getLocalUserId();
		$remote_user = $this->userSession->getRemoteContactID($owner_id);

		if (!empty($table)) {
			$table = DBA::quoteIdentifier($table) . '.';
		}

		// Profile owner - everything is visible
		if ($local_user && ($local_user == $owner_id)) {
			return '';
		}

		if ($remote_user) {
			/*
			 * Authenticated visitor. Unless pre-verified,
			 * check that the contact belongs to this $owner_id
			 * and load the circles the visitor belongs to.
			 * If pre-verified, the caller is expected to have already
			 * done this and passed the circles into this function.
			 */
			$permissionSets = $this->permissionSet->selectByContactId($remote_user, $owner_id);

			$sql_set = '';

			if (count($permissionSets) > 0) {
				$sql_set = sprintf(" OR (" . $table . "`private` = %d AND " . $table . "`wall` AND " . $table . "`psid` IN (", Item::PRIVATE) . implode(',', $permissionSets->column('id')) . "))";
			}

			return sprintf(" AND (" . $table . "`private` != %d", Item::PRIVATE) . $sql_set . ")";
		}

		/*
		 * Construct permissions
		 *
		 * default permissions - anonymous user
		 */
		return sprintf(" AND " . $table . "`private` != %d", Item::PRIVATE);
	}
}
