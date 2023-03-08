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
     * @return int
     * @throws \Exception
     */
    public static function countValidForUser($uid)
	{
		return DBA::count('2fa_recovery_codes', ['uid' => $uid, 'used' => null]);
	}

    /**
     * Checks the provided code is available to use for login by the provided user
     *
     * @param  int $uid User ID
     * @param string $code
     * @return bool
     * @throws \Exception
     */
	public static function existsForUser($uid, $code)
	{
		return DBA::exists('2fa_recovery_codes', ['uid' => $uid, 'code' => $code, 'used' => null]);
	}

    /**
     * Returns a complete list of all recovery codes for the provided user, including the used status
     *
     * @param  int $uid User ID
     * @return array
     * @throws \Exception
     */
	public static function getListForUser($uid)
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
     * @return bool
     * @throws \Exception
     */
	public static function markUsedForUser($uid, $code)
	{
		DBA::update('2fa_recovery_codes', ['used' => DateTimeFormat::utcNow()], ['uid' => $uid, 'code' => $code, 'used' => null]);

		return DBA::affectedRows() > 0;
	}

    /**
     * Generates a fresh set of recovery codes for the provided user.
     * Generates 12 codes constituted of 2 blocks of 6 characters separated by a dash.
     *
     * @param  int $uid User ID
     * @throws \Exception
     */
	public static function generateForUser($uid)
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
				'uid' => $uid,
				'code' => $code,
				'generated' => $generated
			]);
		}
	}

    /**
     * Deletes all the recovery codes for the provided user.
     *
     * @param  int $uid User ID
     * @throws \Exception
     */
	public static function deleteForUser($uid)
	{
		DBA::delete('2fa_recovery_codes', ['uid' => $uid]);
	}

    /**
     * Replaces the existing recovery codes for the provided user by a freshly generated set.
     *
     * @param  int $uid User ID
     * @throws \Exception
     */
	public static function regenerateForUser($uid)
	{
		self::deleteForUser($uid);
		self::generateForUser($uid);
	}
}
