<?php

namespace Friendica\Database\Driver;

abstract class AbstractDriver implements IDriver
{
	/**
	 * The connection state of the database
	 * @var bool
	 */
	protected $isConnected;

	protected $dbUser;
	protected $dbPass;
	protected $dbName;
	protected $dbHost;
	protected $dbPort;
	protected $dbCharset;

	public function __construct($serverAddress, $user, $pass, $db, $charset = null)
	{
		$this->isConnected = false;

		$this->dbHost = $serverAddress;
		$this->dbUser = $user;
		$this->dbPass = $pass;
		$this->dbName = $db;
		$this->dbCharset = $charset;

		$serverAddress = trim($this->dbHost);

		$serverAddressData = explode(':', $serverAddress);
		$server = $serverAddressData[0];

		if (count($serverAddressData) > 1) {
			$this->dbPort = trim($serverAddressData[1]);
		} else {
			$this->dbPort = 0;
		}

		$this->dbHost    = trim($server);
		$this->dbUser    = trim($this->dbUser);
		$this->dbPass    = trim($this->dbPass);
		$this->dbName    = trim($this->dbName);
		$this->dbCharset = trim($this->dbCharset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape($sql)
	{
		// fallback, if no explicit escaping is set for a connection
		return str_replace("'", "\\'", $sql);
	}

	/**
	 * {@inheritDoc}
	 */
	public function replaceParameters($sql, array $args = [])
	{
		$offset = 0;

		foreach ($args AS $param => $value) {
			if (is_int($args[$param]) || is_float($args[$param])) {
				$replace = intval($args[$param]);
			} else {
				$replace = "'" . $this->escape($args[$param]) . "'";
			}

			$pos = strpos($sql, '?', $offset);
			if ($pos !== false) {
				$sql = substr_replace($sql, $replace, $pos, 1);
			}
			$offset = $pos + strlen($replace);
		}

		return $sql;
	}

	/**
	 * @brief Replaces ANY_VALUE() function by MIN() function,
	 *  if the database server does not support ANY_VALUE().
	 *
	 * Considerations for Standard SQL, or MySQL with ONLY_FULL_GROUP_BY (default since 5.7.5).
	 * ANY_VALUE() is available from MySQL 5.7.5 https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html
	 * A standard fall-back is to use MIN().
	 *
	 * @param string $sql An SQL string without the values
	 *
	 * @return string The input SQL string modified if necessary.
	 */
	protected function anyValueFallback($sql)
	{
		$server_info = $this->getServerInfo();
		if (version_compare($server_info, '5.7.5', '<') ||
			(stripos($server_info, 'MariaDB') !== false)) {
			$sql = str_ireplace('ANY_VALUE(', 'MIN(', $sql);
		}
		return $sql;
	}
}
