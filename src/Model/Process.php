<?php
/**
 * @file src/Model/Process.php
 */
namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * @brief functions for interacting with a process
 */
class Process
{
	/**
	 * Insert a new process row. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $command
	 * @param string $pid
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert($command, $pid = null)
	{
		$return = true;

		if (is_null($pid)) {
			$pid = getmypid();
		}

		DBA::transaction();

		if (!DBA::exists('process', ['pid' => $pid])) {
			$return = DBA::insert('process', ['pid' => $pid, 'command' => $command, 'created' => DateTimeFormat::utcNow()]);
		}

		DBA::commit();

		return $return;
	}

	/**
	 * Remove a process row by pid. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $pid
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteByPid($pid = null)
	{
		if ($pid === null) {
			$pid = getmypid();
		}

		return DBA::delete('process', ['pid' => $pid]);
	}

	/**
	 * Clean the process table of inactive physical processes
	 */
	public static function deleteInactive()
	{
		DBA::transaction();

		$processes = DBA::select('process', ['pid']);
		while($process = DBA::fetch($processes)) {
			if (!posix_kill($process['pid'], 0)) {
				self::deleteByPid($process['pid']);
			}
		}

		DBA::commit();
	}
}
