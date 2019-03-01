<?php

namespace Friendica\Database;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Util\Profiler;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use PDO;
use PDOException;
use PDOStatement;

class MysqlDatabase implements IDatabase, IDatabaseLock
{
	const DRIVER_PDO = 'pdo';
	const DRIVER_MYSQLI = 'mysqli';
	const DRIVER_INVALID = null;

	/**
	 * The connection state of the database
	 * @var bool
	 */
	private $connected;

	private $dbUser;
	private $dbPass;
	private $dbName;
	private $dbHost;
	private $dbCharset;

	private $serverInfo;

	private $profiler;
	private $configCache;

	/**
	 * The connection to the database
	 * @var PDO|mysqli
	 */
	private $connection;
	private $driver;

	public function __construct(IConfigCache $configCache, Profiler $profiler, $serveraddr, $user, $pass, $db, $charset = null)
	{
		$this->configCache = $configCache;
		$this->profiler = $profiler;

		$this->dbHost = $serveraddr;
		$this->dbUser = $user;
		$this->dbPass = $pass;
		$this->dbName = $db;
		$this->dbCharset = $charset;

		$this->serverInfo = '';

		$this->connect();
	}

	public function isConnected()
	{
		return $this->connected;
	}

	private function connect()
	{
		if (!is_null($this->connection) && $this->isConnected()) {
			return;
		}

		$port = 0;
		$serveraddr = trim($this->dbHost);

		$serverdata = explode(':', $serveraddr);
		$server = $serverdata[0];

		if (count($serverdata) > 1) {
			$port = trim($serverdata[1]);
		}

		$server = trim($server);
		$user = trim($this->dbUser);
		$pass = trim($this->dbPass);
		$db = trim($this->dbName);
		$charset = trim($this->dbCharset);

		if (!(strlen($server) && strlen($user))) {
			$this->connected = false;
			return;
		}

		if (class_exists('\PDO') && in_array('mysql', PDO::getAvailableDrivers())) {
			$this->driver = self::DRIVER_PDO;
			$connect = "mysql:host=".$server.";dbname=".$db;

			if ($port > 0) {
				$connect .= ";port=".$port;
			}

			if ($charset) {
				$connect .= ";charset=".$charset;
			}

			try {
				$this->connection = @new PDO($connect, $user, $pass);
				$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$this->connected = true;
			} catch (PDOException $e) {
				/// @TODO At least log exception, don't ignore it!
			}
		}

		if (!$this->connected && class_exists('\mysqli')) {
			$this->driver = self::DRIVER_MYSQLI;

			if ($port > 0) {
				$this->connection = @new mysqli($server, $user, $pass, $db, $port);
			} else {
				$this->connection = @new mysqli($server, $user, $pass, $db);
			}

			if (!mysqli_connect_errno()) {
				$this->connected = true;

				if ($charset) {
					$this->connection->set_charset($charset);
				}
			}
		}

		// No suitable SQL driver was found.
		if (!$this->connected) {
			$this->driver     = self::DRIVER_INVALID;
			$this->connection = null;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function disconnect()
	{
		if (is_null($this->connection)) {
			return;
		}

		switch ($this->driver) {
			case self::DRIVER_PDO:
				$this->connection = null;
				break;
			case self::DRIVER_MYSQLI:
				$this->connection->close();
				$this->connection = null;
				break;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function reconnect()
	{
		$this->disconnect();
		$this->connect();

		return $this->connected;
	}

	/**
	 * {@inheritdoc}
	 * @return mixed|mysqli|PDO
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * {@inheritdoc}
	 */
	public function serverInfo()
	{
		if (empty($this->serverInfo)) {
			switch ($this->driver) {
				case self::DRIVER_PDO:
					$this->serverInfo = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
					break;
				case self::DRIVER_MYSQLI:
					$this->serverInfo = $this->connection->server_info;
					break;
			}
		}

		return $this->serverInfo;
	}

	/**
	 * {@inheritdoc}
	 * @throws \Exception
	 */
	public function getDatabaseName()
	{
		$ret = $this->p("SELECT DATABASE() AS `db`");
		$data = $this->toArray($ret);
		return $data[0]['db'];
	}

	public function exists($table, array $condition)
	{
		return DBA::exists($table, $condition);
	}

	public function count($table, array $condition = [])
	{
		return DBA::count($table, $condition);
	}

	public function fetch($stmt)
	{
		return DBA::fetch($stmt);
	}

	public function transaction()
	{
		return DBA::transaction();
	}

	public function commit()
	{
		return DBA::commit();
	}

	public function rollback()
	{
		return DBA::rollback();
	}

	public function insert($table, array $param, $on_duplicate_update = false)
	{
		return DBA::insert($table, $param, $on_duplicate_update);
	}

	public function delete($table, array $conditions, $cascade = true)
	{
		return DBA::delete($table, $conditions, [$cascade]);
	}

	public function update($table, array $fields, array $condition, array $old_fields = [])
	{
		return DBA::delete($table, $fields, $condition, $old_fields);
	}

	public function select($table, array $fields = [], array $condition = [], array $params = [])
	{
		return DBA::select($table, $fields, $condition, $params);
	}

	public function selectFirst($table, array $fields = [], array $condition = [], $params = [])
	{
		return DBA::selectFirst($table, $fields, $condition, $params);
	}

	public function lock($table)
	{
		return DBA::lock($table);
	}

	public function unlock()
	{
		return DBA::unlock();
	}
}
