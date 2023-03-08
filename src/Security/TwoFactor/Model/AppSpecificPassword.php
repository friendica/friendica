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
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;
use PragmaRX\Random\Random;

/**
 * Manages users' two-factor recovery hashed_passwords in the 2fa_app_specific_passwords table
 */
class AppSpecificPassword
{
	public static function countForUser($uid)
	{
		return DBA::count('2fa_app_specific_password', ['uid' => $uid]);
	}

	public static function checkDuplicateForUser($uid, $description)
	{
		return DBA::exists('2fa_app_specific_password', ['uid' => $uid, 'description' => $description]);
	}

	/**
	 * Checks the provided hashed_password is available to use for login by the provided user
	 *
	 * @param int    $uid User ID
	 * @param string $plaintextPassword
	 * @return bool
	 * @throws \Exception
	 */
	public static function authenticateUser($uid, $plaintextPassword)
	{
		$appSpecificPasswords = self::getListForUser($uid);

		$return = false;

		foreach ($appSpecificPasswords as $appSpecificPassword) {
			if (password_verify($plaintextPassword, $appSpecificPassword['hashed_password'])) {
				$fields = ['last_used' => DateTimeFormat::utcNow()];
				if (password_needs_rehash($appSpecificPassword['hashed_password'], PASSWORD_DEFAULT)) {
					$fields['hashed_password'] = User::hashPassword($plaintextPassword);
				}

				self::update($appSpecificPassword['id'], $fields);

				$return |= true;
			}
		}

		return $return;
	}

    /**
     * Returns a complete list of all recovery hashed_passwords for the provided user, including the used status
     *
     * @param  int $uid User ID
     * @return array
     * @throws \Exception
     */
	public static function getListForUser($uid)
	{
		$appSpecificPasswordsStmt = DBA::select('2fa_app_specific_password', ['id', 'description', 'hashed_password', 'last_used'], ['uid' => $uid]);

		$appSpecificPasswords = DBA::toArray($appSpecificPasswordsStmt);

		array_walk($appSpecificPasswords, function (&$value) {
			$value['ago']   = Temporal::getRelativeDate($value['last_used']);
			$value['utc']   = $value['last_used'] ? DateTimeFormat::utc($value['last_used'], 'c') : '';
			$value['local'] = $value['last_used'] ? DateTimeFormat::local($value['last_used'], 'r') : '';
		});

		return $appSpecificPasswords;
	}

    /**
     * Generates a new app specific password for the provided user and hashes it in the database.
     *
     * @param  int    $uid         User ID
     * @param  string $description Password description
     * @return array The new app-specific password data structure with the plaintext password added
     * @throws \Exception
     */
	public static function generateForUser(int $uid, $description)
	{
		$Random = (new Random())->size(40);

		$plaintextPassword = $Random->get();

		$generated = DateTimeFormat::utcNow();

		$fields = [
			'uid' => $uid,
			'description' => $description,
			'hashed_password' => User::hashPassword($plaintextPassword),
			'generated' => $generated,
		];

		DBA::insert('2fa_app_specific_password', $fields);

		$fields['id'] = DBA::lastInsertId();
		$fields['plaintext_password'] = $plaintextPassword;

		return $fields;
	}

	private static function update($appSpecificPasswordId, $fields)
	{
		return DBA::update('2fa_app_specific_password', $fields, ['id' => $appSpecificPasswordId]);
	}

	/**
	 * Deletes all the recovery hashed_passwords for the provided user.
	 *
	 * @param int $uid User ID
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteAllForUser(int $uid)
	{
		return DBA::delete('2fa_app_specific_password', ['uid' => $uid]);
	}

	/**
	 * @param int $uid
	 * @param int $app_specific_password_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteForUser(int $uid, int $app_specific_password_id)
	{
		return DBA::delete('2fa_app_specific_password', ['id' => $app_specific_password_id, 'uid' => $uid]);
	}
}
