<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\App;

use Detection\MobileDetect;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;

/**
 * Mode of the current Friendica Node
 *
 * @package Friendica\App
 */
class Mode
{
	const LOCALCONFIGPRESENT  = 1;
	const DBAVAILABLE         = 2;
	const DBCONFIGAVAILABLE   = 4;
	const MAINTENANCEDISABLED = 8;

	const UNDEFINED = 0;
	const INDEX = 1;
	const DAEMON = 2;
	const WORKER = 3;

	const BACKEND_CONTENT_TYPES = ['application/jrd+json', 'text/xml',
		'application/rss+xml', 'application/atom+xml', 'application/activity+json'];

	/**
	 * A list of modules, which are backend methods
	 *
	 * @var array
	 */
	const BACKEND_MODULES = [
		'_well_known',
		'api',
		'dfrn_notify',
		'feed',
		'fetch',
		'followers',
		'following',
		'hcard',
		'hostxrd',
		'inbox',
		'manifest',
		'nodeinfo',
		'noscrape',
		'objects',
		'outbox',
		'poco',
		'pubsub',
		'pubsubhubbub',
		'receive',
		'rsd_xml',
		'salmon',
		'statistics_json',
		'xrd',
	];

	/***
	 * @var int The mode of this Application
	 *
	 */
	private $mode;

	/***
	 * @var int Who executes this Application
	 *
	 */
	private $executor = self::UNDEFINED;

	/**
	 * @var bool True, if the call is a backend call
	 */
	private $isBackend;

	/**
	 * @var bool True, if the call is a ajax call
	 */
	private $isAjax;

	/**
	 * @var bool True, if the call is from a mobile device
	 */
	private $isMobile;

	/**
	 * @var bool True, if the call is from a tablet device
	 */
	private $isTablet;

	public function __construct(int $mode = 0, bool $isBackend = false, bool $isAjax = false, bool $isMobile = false, bool $isTablet = false)
	{
		$this->mode      = $mode;
		$this->isBackend = $isBackend;
		$this->isAjax    = $isAjax;
		$this->isMobile  = $isMobile;
		$this->isTablet  = $isTablet;
	}

	/**
	 * Sets the App mode
	 *
	 * - App::MODE_INSTALL    : Either the database connection can't be established or the config table doesn't exist
	 * - App::MODE_MAINTENANCE: The maintenance mode has been set
	 * - App::MODE_NORMAL     : Normal run with all features enabled
	 *
	 * @return Mode returns the determined mode
	 *
	 * @throws \Exception
	 */
	public function determine(string $basePath, Database $database, IManageConfigValues $config): Mode
	{
		$mode = 0;

		if (!file_exists($basePath . '/config/local.config.php') &&
			!file_exists($basePath . '/config/local.ini.php') &&
			!file_exists($basePath . '/.htconfig.php')) {
			return new Mode($mode);
		}

		$mode |= Mode::LOCALCONFIGPRESENT;

		if (!$database->connected()) {
			return new Mode($mode);
		}

		$mode |= Mode::DBAVAILABLE;

		if (!empty($config->get('system', 'maintenance'))) {
			return new Mode($mode);
		}

		$mode |= Mode::MAINTENANCEDISABLED;

		return new Mode($mode, $this->isBackend, $this->isAjax, $this->isMobile, $this->isTablet);
	}

	/**
	 * Checks if the site is called via a backend process
	 *
	 * @param bool             $isBackend    True, if the call is from a backend script (daemon, worker, ...)
	 * @param array            $server       The $_SERVER variable
	 * @param Arguments        $args         The Friendica App arguments
	 * @param MobileDetect     $mobileDetect The mobile detection library
	 *
	 * @return Mode returns the determined mode
	 */
	public function determineRunMode(bool $isBackend, array $server, Arguments $args, MobileDetect $mobileDetect): Mode
	{
		foreach (self::BACKEND_CONTENT_TYPES as $type) {
			if (strpos(strtolower($server['HTTP_ACCEPT'] ?? ''), $type) !== false) {
				$isBackend = true;
			}
		}

		$isBackend = $isBackend || in_array($args->getModuleName(), static::BACKEND_MODULES);
		$isMobile  = $mobileDetect->isMobile();
		$isTablet  = $mobileDetect->isTablet();
		$isAjax    = strtolower($server['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest';

		return new Mode($this->mode, $isBackend, $isAjax, $isMobile, $isTablet);
	}

	/**
	 * Checks, if the Friendica Node has the given mode
	 *
	 * @param int $mode A mode to test
	 *
	 * @return bool returns true, if the mode is set
	 */
	public function has(int $mode): bool
	{
		return ($this->mode & $mode) > 0;
	}

	/**
	 * Set the execution mode
	 *
	 * @param integer $executor Execution Mode
	 * @return void
	 */
	public function setExecutor(int $executor)
	{
		$this->executor = $executor;

		// Daemon and worker are always backend
		if (in_array($executor, [self::DAEMON, self::WORKER])) {
			$this->isBackend = true;
		}
	}

	/*isBackend = true;*
	 * get the execution mode
	 *
	 * @return int Execution Mode
	 */
	public function getExecutor(): int
	{
		return $this->executor;
	}

	/**
	 * Install mode is when the local config file is missing or the database isn't available.
	 *
	 * @return bool Whether installation mode is active (local/database configuration files present or not)
	 */
	public function isInstall(): bool
	{
		return !$this->has(Mode::LOCALCONFIGPRESENT) ||
		       !$this->has(MODE::DBAVAILABLE);
	}

	/**
	 * Normal mode is when the local config file is set, the DB schema is installed and the maintenance mode is off.
	 *
	 * @return bool
	 */
	public function isNormal(): bool
	{
		return $this->has(Mode::LOCALCONFIGPRESENT) &&
		       $this->has(Mode::DBAVAILABLE) &&
		       $this->has(Mode::MAINTENANCEDISABLED);
	}

	/**
	 * Returns true, if the call is from a backend node (f.e. from a worker)
	 *
	 * @return bool Is it a backend call
	 */
	public function isBackend(): bool
	{
		return $this->isBackend;
	}

	/**
	 * Check if request was an AJAX (xmlhttprequest) request.
	 *
	 * @return bool true if it was an AJAX request
	 */
	public function isAjax(): bool
	{
		return $this->isAjax;
	}

	/**
	 * Check if request was a mobile request.
	 *
	 * @return bool true if it was an mobile request
	 */
	public function isMobile(): bool
	{
		return $this->isMobile;
	}

	/**
	 * Check if request was a tablet request.
	 *
	 * @return bool true if it was an tablet request
	 */
	public function isTablet(): bool
	{
		return $this->isTablet;
	}
}
