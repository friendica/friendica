<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Content\Pager;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * Class interacting with the register database table
 */
class Register
{
	/**
	 * Return the list of pending registrations
	 *
	 * @param int $start Start count (Default is 0)
	 * @param int $count Count of the items per page (Default is @see Pager::ITEMS_PER_PAGE)
	 * @return array|bool Array on succes, false on failure
	 * @throws \Exception
	 */
	public static function getPending(int $start = 0, int $count = Pager::ITEMS_PER_PAGE)
	{
		return DBA::selectToArray('pending-view', [], [], ['limit' => [$start, $count]]);
	}

	/**
	 * Returns the pending user based on a given user id
	 *
	 * @param int $uid The user id
	 *
	 * @return array|bool Array on succes, false on failure
	 * @throws \Exception
	 */
	public static function getPendingForUser(int $uid)
	{
		return DBA::selectFirst('pending-view', [], ['uid' => $uid, 'self' => true]);
	}

	/**
	 * Returns the pending registration count
	 *
	 * @return int
	 * @throws \Exception
	 */
	public static function getPendingCount(): int
	{
		return DBA::count('pending-view', ['self' => true]);
	}

	/**
	 * Returns the register record associated with the provided hash
	 *
	 * @param  string $hash
	 * @return array|bool Array on succes, false on failure
	 * @throws \Exception
	 */
	public static function getByHash(string $hash)
	{
		return DBA::selectFirst('register', [], ['hash' => $hash]);
	}

	/**
	 * Returns true if a register record exists with the provided hash
	 *
	 * @param  string $hash
	 * @return boolean
	 * @throws \Exception
	 */
	public static function existsByHash(string $hash): bool
	{
		return DBA::exists('register', ['hash' => $hash]);
	}

	/**
	 * Creates a register record for an invitation and returns the auto-generated code for it
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function createForInvitation(): string
	{
		$code = Strings::getRandomName(8) . random_int(1000, 9999);

		$fields = [
			'hash'    => $code,
			'created' => DateTimeFormat::utcNow()
		];

		DBA::insert('register', $fields);

		return $code;
	}

	/**
	 * Creates a register record for approval
	 * Checks for the existence of the provided user id
	 *
	 * @param integer $uid      The ID of the user needing approval
	 * @param string  $language The registration language
	 * @param string  $note     An additional message from the user
	 * @return void
	 * @throws \OutOfBoundsException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 */
	public static function createForApproval(int $uid, string $language, string $note = ''): void
	{
		$hash = Strings::getRandomHex();

		if (!$uid) {
			throw new \OutOfBoundsException("User ID can't be empty");
		}

		if (!User::exists($uid)) {
			throw new HTTPException\NotFoundException("User ID doesn't exist");
		}

		$fields = [
			'hash'     => $hash,
			'created'  => DateTimeFormat::utcNow(),
			'uid'      => $uid,
			'password' => '', // Obsolete, slated for deletion
			'language' => $language,
			'note'     => $note
		];

		if (!DBA::insert('register', $fields)) {
			throw new HTTPException\InternalServerErrorException('Unable to insert a `register` record');
		}
	}

	/**
	 * Deletes a register record by the provided hash and returns the success of the database deletion
	 *
	 * @param  string $hash
	 * @return boolean
	 * @throws \Exception
	 */
	public static function deleteByHash(string $hash): bool
	{
		return DBA::delete('register', ['hash' => $hash]);
	}
}
