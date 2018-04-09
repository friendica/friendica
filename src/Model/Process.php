<?php
/**
 * @file src/Model/Process.php
 */
namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Util\DateTimeFormat;
use dba;

require_once 'include/dba.php';

/**
 * @brief functions for interacting with a process
 */
class Process extends BaseObject
{
	/**
	 * Insert a new process row. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $command
	 * @param string $pid
	 * @return bool
	 */
	public static function insert($command, $pid = null)
	{
		$return = true;

		if (is_null($pid)) {
			$pid = getmypid();
		}

		dba::transaction();

		if (!dba::exists('process', ['pid' => $pid])) {
			$return = dba::insert('process', ['pid' => $pid, 'command' => $command, 'created' => DateTimeFormat::utcNow()]);
		}

		dba::commit();

		return $return;
	}

	/**
	 * Remove a process row by pid. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $pid
	 * @return bool
	 */
	public static function deleteByPid($pid = null)
	{
		if ($pid === null) {
			$pid = getmypid();
		}

		return dba::delete('process', ['pid' => $pid]);
	}

	/**
	 * Clean the process table of inactive physical processes
	 */
	public static function deleteInactive()
	{
		dba::transaction();

		$processes = dba::select('process', ['pid']);
		while($process = dba::fetch($processes)) {
			if (!posix_kill($process['pid'], 0)) {
				self::deleteByPid($process['pid']);
			}
		}

		dba::commit();
	}
}
