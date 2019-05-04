<?php

namespace Friendica\App;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Database\Database;
use Friendica\Util\BasePath;

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

	/**
	 * @var Database
	 */
	private $database;

	/**
	 * @var IConfigCache
	 */
	private $configCache;

	public function __construct(BasePath $basepath, Database $database, IConfigCache $configCache)
	{
		$this->basepath    = $basepath->getPath();
		$this->database    = $database;
		$this->configCache = $configCache;
		$this->mode        = 0;
	}

	/**
	 * Sets the App mode
	 *
	 * - App::MODE_INSTALL    : Either the database connection can't be established or the config table doesn't exist
	 * - App::MODE_MAINTENANCE: The maintenance mode has been set
	 * - App::MODE_NORMAL     : Normal run with all features enabled
	 *
	 * @param string $basePath the Basepath of the Application
	 *
	 * @return Mode returns itself
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function determine($basePath = null)
	{
		if (!empty($basePath)) {
			$this->basepath = $basePath;
		}

		$this->mode = 0;

		if (!file_exists($this->basepath . '/config/local.config.php')
			&& !file_exists($this->basepath . '/config/local.ini.php')
			&& !file_exists($this->basepath . '/.htconfig.php')) {
			return $this;
		}

		$this->mode |= Mode::LOCALCONFIGPRESENT;

		if (!$this->database->connected()) {
			return $this;
		}

		$this->mode |= Mode::DBAVAILABLE;

		if ($this->database->fetchFirst("SHOW TABLES LIKE 'config'") === false) {
			return $this;
		}

		$this->mode |= Mode::DBCONFIGAVAILABLE;

		if ($this->configCache->get('system', 'maintenance') ||
			$this->database->selectFirst('config', ['cat' => 'system', 'key' => 'maintenance'])) {
			return $this;
		}

		$this->mode |= Mode::MAINTENANCEDISABLED;

		return $this;
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
