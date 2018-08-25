<?php
/**
 * @file src/Worker/RemoveUser.php
 * @brief Removes orphaned data from deleted users
 */
namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Model\Item;

require_once 'include/dba.php';

class RemoveUser {
	public static function execute($uid)
	{
		// Only delete if the user is archived
		$condition = ['account_removed' => true, 'uid' => $uid];
		if (!DBA::exists('user', $condition)) {
			return;
		}

		// Now we delete all user items
		Item::delete(['uid' => $uid], PRIORITY_LOW);
	}
}
