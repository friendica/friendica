<?php

namespace Friendica\Database;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Util\Profiler;

class MysqlDatabase implements IDatabase, IDatabaseLock
{
	private $connected;

	public function __construct(IConfigCache $configCache, Profiler $profiler, $serveraddr, $user, $pass, $db, $charset = null)
	{
		$this->connected = DBA::connect($configCache, $profiler, $serveraddr, $user, $pass, $db, $charset);
	}

	function isConnected()
	{
		return $this->connected;
	}

	function disconnect()
	{
		DBA::disconnect();
	}

	function reconnect()
	{
		DBA::reconnect();
		$this->connected = DBA::connected();
	}

	function getConnection()
	{
		return DBA::getConnection();
	}

	function serverInfo()
	{
		return DBA::serverInfo();
	}

	function databaseName()
	{
		return DBA::databaseName();
	}

	function exists($table, array $condition)
	{
		return DBA::exists($table, $condition);
	}

	function count($table, array $condition = [])
	{
		return DBA::count($table, $condition);
	}

	function fetch($stmt)
	{
		return DBA::fetch($stmt);
	}

	function transaction()
	{
		return DBA::transaction();
	}

	function commit()
	{
		return DBA::commit();
	}

	function rollback()
	{
		return DBA::rollback();
	}

	function insert($table, array $param, $on_duplicate_update = false)
	{
		return DBA::insert($table, $param, $on_duplicate_update);
	}

	function delete($table, array $conditions, $cascade = true)
	{
		return DBA::delete($table, $conditions, [$cascade]);
	}

	function update($table, array $fields, array $condition, array $old_fields = [])
	{
		return DBA::delete($table, $fields, $condition, $old_fields);
	}

	function select($table, array $fields = [], array $condition = [], array $params = [])
	{
		return DBA::select($table, $fields, $condition, $params);
	}

	function selectFirst($table, array $fields = [], array $condition = [], $params = [])
	{
		return DBA::selectFirst($table, $fields, $condition, $params);
	}

	function lock($table)
	{
		return DBA::lock($table);
	}

	function unlock()
	{
		return DBA::unlock();
	}
}
