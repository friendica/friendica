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
}
