<?php

/**
 * @file src/Model/Register.php
 */
namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * Class interacting with the register database table
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Register
{
	/**
	 * Return the list of pending registrations
	 *
	 * @return array
	 */
	public static function getPending()
	{
		$stmt = DBA::p(
			"SELECT `register`.*, `contact`.`name`, `user`.`email`
			FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `register`.`uid` = `user`.`uid`"
		);

		return DBA::toArray($stmt);
	}

	/**
	 * Returns the pending registration count
	 *
	 * @return int
	 */
	public static function getPendingCount()
	{
		$register = DBA::fetchFirst(
			"SELECT COUNT(*) AS `count`
			FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid` AND `contact`.`self`"
		);

		return $register['count'];
	}

	/**
	 * Returns the register record associated with the provided hash
	 *
	 * @param  string $hash
	 * @return array
	 */
	public static function getByHash($hash)
	{
		return DBA::selectFirst('register', [], ['hash' => $hash]);
	}

	/**
	 * Returns true if a register record exists with the provided hash
	 *
	 * @param  string  $hash
	 * @return boolean
	 */
	public static function existsByHash($hash)
	{
		return DBA::exists('register', ['hash' => $hash]);
	}

	/**
	 * Creates a register record for an invitation and returns the auto-generated code for it
	 *
	 * @return string
	 */
	public static function createForInvitation()
	{
		$code = autoname(8) . srand(1000, 9999);

		$fields = [
			'hash' => $code,
			'created' => DateTimeFormat::utcNow()
		];

		DBA::insert('register', $fields);

		return $code;
	}

	/**
	 * Creates a register record for approval and returns the success of the database insert
	 * Checks for the existence of the provided user id
	 *
	 * @param  integer $uid      The ID of the user needing approval
	 * @param  string  $language The registration language
	 * @param  string  $note     An additional message from the user
	 * @return boolean
	 */
	public static function createForApproval($uid, $language, $note = '')
	{
		$hash = random_string();

		if (!User::exists($uid)) {
			return false;
		}

		$fields = [
			'hash'     => $hash,
			'created'  => DateTimeFormat::utcNow(),
			'uid'      => $uid,
			'password' => '', // Obsolete, slated for deletion
			'language' => $language,
			'note'     => $note
		];

		return DBA::insert('register', $fields);
	}

	/**
	 * Deletes a register record by the provided hash and returns the success of the database deletion
	 *
	 * @param  string  $hash
	 * @return boolean
	 */
	public static function deleteByHash($hash)
	{
		return DBA::delete('register', ['hash' => $hash]);
	}
}
