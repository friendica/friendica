<?php
/**
 * @file src/Worker/DBUpdate.php
 * @brief This file is called when the database structure needs to be updated
 */
namespace Friendica\Worker;

use Friendica\Core\Update;

class DBUpdate
{
	public static function execute()
	{
		Update::run();
	}
}
