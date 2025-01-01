<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\TwoFactor\Model;

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use PragmaRX\Random\Random;
use PragmaRX\Recovery\Recovery;

/**
 * Manages users' two-factor recovery codes in the 2fa_recovery_codes table
 */
class RecoveryCode
{
	/**
	 * Returns the number of code the provided users can still use to replace a TOTP code
	 *
	 * @param int $uid User ID
	 *
	 * @return int
	 * @throws \Exception
	 */
	public static function countValidForUser(int $uid): int
	{
		return DBA::count('2fa_recovery_codes', ['uid' => $uid, 'used' => null]);
	}

	/**
	 * Checks the provided code is available to use for login by the provided user
	 *
	 * @param int $uid User ID
	 * @param string $code
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function existsForUser(int $uid, string $code): bool
	{
		return DBA::exists('2fa_recovery_codes', ['uid' => $uid, 'code' => $code, 'used' => null]);
	}

	/**
	 * Returns a complete list of all recovery codes for the provided user, including the used status
	 *
	 * @param  int $uid User ID
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getListForUser(int $uid): array
	{
		$codesStmt = DBA::select('2fa_recovery_codes', ['code', 'used'], ['uid' => $uid]);

		return DBA::toArray($codesStmt);
	}

	/**
	 * Marks the provided code as used for the provided user.
	 * Returns false if the code doesn't exist for the user or it has been used already.
	 *
	 * @param  int $uid User ID
	 * @param string $code
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function markUsedForUser(int $uid, string $code): bool
	{
		DBA::update('2fa_recovery_codes', ['used' => DateTimeFormat::utcNow()], ['uid' => $uid, 'code' => $code, 'used' => null]);

		return DBA::affectedRows() > 0;
	}

	/**
	 * Generates a fresh set of recovery codes for the provided user.
	 * Generates 12 codes constituted of 2 blocks of 6 characters separated by a dash.
	 *
	 * @param  int $uid User ID
	 * @return void
	 *
	 * @throws \Exception
	 */
	public static function generateForUser(int $uid)
	{
		$Random = (new Random())->pattern('[a-z0-9]');

		$RecoveryGenerator = new Recovery($Random);

		$codes = $RecoveryGenerator
			->setCount(12)
			->setBlocks(2)
			->setChars(6)
			->lowercase(true)
			->toArray();

		$generated = DateTimeFormat::utcNow();
		foreach ($codes as $code) {
			DBA::insert('2fa_recovery_codes', [
				'uid'       => $uid,
				'code'      => $code,
				'generated' => $generated
			]);
		}
	}

	/**
	 * Deletes all the recovery codes for the provided user.
	 *
	 * @param  int $uid User ID
	 * @return void
	 *
	 * @throws \Exception
	 */
	public static function deleteForUser(int $uid)
	{
		DBA::delete('2fa_recovery_codes', ['uid' => $uid]);
	}

	/**
	 * Replaces the existing recovery codes for the provided user by a freshly generated set.
	 *
	 * @param  int $uid User ID
	 * @return void
	 *
	 * @throws \Exception
	 */
	public static function regenerateForUser(int $uid)
	{
		self::deleteForUser($uid);
		self::generateForUser($uid);
	}
}
