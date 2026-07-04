<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public const LOCALCONFIGPRESENT  = 1;
	public const DBAVAILABLE         = 2;
	public const DBCONFIGAVAILABLE   = 4;
	public const MAINTENANCEDISABLED = 8;

	public const UNDEFINED = 0;
	public const INDEX     = 1;
	public const DAEMON    = 2;
	public const WORKER    = 3;

	public const BACKEND_CONTENT_TYPES = ['application/jrd+json', 'text/xml',
		'application/rss+xml', 'application/atom+xml', 'application/activity+json'];

	/**
	 * A list of modules, which are backend methods
	 *
	 * @var array
	 */
	public const BACKEND_MODULES = [
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
		'receive',
		'rsd_xml',
		'statistics_json',
		'xrd',
	];

	/***
	 * @var int Who executes this Application
	 *
	 */
	private $executor = self::UNDEFINED;

	public function __construct(
		private readonly int $mode = 0,
		/**
		 * @var bool True, if the call is a backend call
		 */
		private bool $isBackend = false,
		/**
		 * @var bool True, if the call is a ajax call
		 */
		private readonly bool $isAjax = false,
		/**
		 * @var bool True, if the call is from a mobile device
		 */
		private readonly bool $isMobile = false,
		/**
		 * @var bool True, if the call is from a tablet device
		 */
		private readonly bool $isTablet = false,
	) {}

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

		if (!file_exists($basePath . '/config/local.config.php')
			&& !file_exists($basePath . '/config/local.ini.php')
			&& !file_exists($basePath . '/.htconfig.php')) {
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
			if (str_contains(strtolower($server['HTTP_ACCEPT'] ?? ''), $type)) {
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
		return !$this->has(Mode::LOCALCONFIGPRESENT)
			   || !$this->has(Mode::DBAVAILABLE);
	}

	/**
	 * Normal mode is when the local config file is set, the DB schema is installed and the maintenance mode is off.
	 *
	 * @return bool
	 */
	public function isNormal(): bool
	{
		return $this->has(Mode::LOCALCONFIGPRESENT)
			   && $this->has(Mode::DBAVAILABLE)
			   && $this->has(Mode::MAINTENANCEDISABLED);
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
