<?php
/**
 * @file src/Worker/DBUpdate.php
 * @brief This file is called when the database structure needs to be updated
 */
namespace Friendica\Worker;

use Friendica\Core\Config;

class DBUpdate
{
	public static function execute()
	{
		$a = \Friendica\BaseObject::getApp();

		// We are deleting the latest dbupdate entry.
		// This is done to avoid endless loops because the update was interupted.
		Config::delete('database', 'dbupdate_' . Config::get('system', 'db_update_version'));

		update_db($a);
	}
}
