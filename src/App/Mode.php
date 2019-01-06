<?php

namespace Friendica\App;

use Friendica\Core\Config;
use Friendica\Database\DBA;

/**
 * Mode of the current Friendica Node
 *
 * @package Friendica\App
 */
class Mode
{
	const LOCALCONFIGPRESENT = 1;
	const DBAVAILABLE = 2;
	const DBCONFIGAVAILABLE = 4;
	const MAINTENANCEDISABLED = 8;

	/***
	 * @var int the mode of this Application
	 *
	 */
	private $mode;

	/**
	 * @var string the basepath of the application
	 */
	private $basepath;

	public function __construct($basepath = '')
	{
		$this->basepath = $basepath;
		$this->mode = 0;
	}

	/**
	 * Sets the App mode
	 *
	 * - App::MODE_INSTALL    : Either the database connection can't be established or the config table doesn't exist
	 * - App::MODE_MAINTENANCE: The maintenance mode has been set
	 * - App::MODE_NORMAL     : Normal run with all features enabled
	 *
	 * @param string $basepath the Basepath of the Application
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function determine($basepath = null)
	{
		if (!empty($basepath)) {
			$this->basepath = $basepath;
		}

		$this->mode = 0;

		if (!file_exists($this->basepath . '/config/local.config.php')
			&& !file_exists($this->basepath . '/config/local.ini.php')
			&& !file_exists($this->basepath . '/.htconfig.php')) {
			return;
		}

		$this->mode |= Mode::LOCALCONFIGPRESENT;

		if (!DBA::connected()) {
			return;
		}

		$this->mode |= Mode::DBAVAILABLE;

		if (DBA::fetchFirst("SHOW TABLES LIKE 'config'") === false) {
			return;
		}

		$this->mode |= Mode::DBCONFIGAVAILABLE;

		if (Config::get('system', 'maintenance')) {
			return;
		}

		$this->mode |= Mode::MAINTENANCEDISABLED;
	}

	/**
	 * Checks, if the Friendica Node has the given mode
	 *
	 * @param int $mode A mode to test
	 *
	 * @return bool returns true, if the mode is set
	 */
	public function has($mode)
	{
		return ($this->mode & $mode) > 0;
	}


	/**
	 * Install mode is when the local config file is missing or the DB schema hasn't been installed yet.
	 *
	 * @return bool
	 */
	public function isInstall()
	{
		return !$this->has(Mode::LOCALCONFIGPRESENT) ||
			!$this->has(MODE::DBCONFIGAVAILABLE);
	}

	/**
	 * Normal mode is when the local config file is set, the DB schema is installed and the maintenance mode is off.
	 *
	 * @return bool
	 */
	public function isNormal()
	{
		return $this->has(Mode::LOCALCONFIGPRESENT) &&
			$this->has(Mode::DBAVAILABLE) &&
			$this->has(Mode::DBCONFIGAVAILABLE) &&
			$this->has(Mode::MAINTENANCEDISABLED);
	}
}
