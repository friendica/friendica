<?php

namespace Friendica\Database;

interface IDatabaseLock
{
	/**
	 * Locks a table for exclusive write access
	 *
	 * @param string $table The table name
	 *
	 * @return bool Was the lock successful?
	 */
	function lock($table);

	/**
	 * Unlocks all locked tables
	 *
	 * @return bool Was the unlock successful?
	 */
	function unlock();
}
