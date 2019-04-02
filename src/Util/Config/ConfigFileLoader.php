<?php

namespace Friendica\Util\Config;

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config\Cache\IConfigCache;

/**
 * The ConfigFileLoader loads config-files and stores them in a IConfigCache ( @see IConfigCache )
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigFileLoader extends ConfigFileManager
{
	/**
	 * @var App\Mode
	 */
	private $appMode;

	public function __construct($baseDir, App\Mode $mode)
	{
		parent::__construct($baseDir);
		$this->appMode = $mode;
	}

	/**
	 * Load the configuration files into an configuration cache
	 *
	 * First loads the default value for all the configuration keys, then the legacy configuration files, then the
	 * expected local.config.php
	 *
	 * @param IConfigCache $config The config cache to load to
	 * @param bool         $raw    Setup the raw config format
	 */
	public function setupCache(IConfigCache $config, $raw = false)
	{
		$config->load($this->loadCoreConfig('defaults'));
		$config->load($this->loadCoreConfig('settings'));

		$config->load($this->loadLegacyConfig('htpreconfig'), true);
		$config->load($this->loadLegacyConfig('htconfig'), true);

		$config->load($this->loadCoreConfig('local'), true);

		// In case of install mode, add the found basepath (because there isn't a basepath set yet
		if (!$raw && ($this->appMode->isInstall() || empty($config->get('system', 'basepath')))) {
			// Setting at least the basepath we know
			$config->set('system', 'basepath', $this->baseDir);
		}
	}

	/**
	 * Tries to load the specified core-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration (default is empty, which means 'local')
	 *
	 * @return array The config array (empty if no config found)
	 */
	public function loadCoreConfig($name = '')
	{
		if (!empty($this->getConfigFullName($name))) {
			return $this->loadConfigFile($this->getConfigFullName($name));
		} elseif (!empty($this->getIniFullName($name))) {
			return $this->loadINIConfigFile($this->getIniFullName($name));
		} else {
			return [];
		}
	}

	/**
	 * Tries to load the specified addon-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 */
	public function loadAddonConfig($name)
	{
		$filepath = $this->baseDir . DIRECTORY_SEPARATOR . // /var/www/html/
			Addon::DIRECTORY       . DIRECTORY_SEPARATOR . // addon/
			$name                  . DIRECTORY_SEPARATOR . // openstreetmap/
			self::SUBDIRECTORY     . DIRECTORY_SEPARATOR . // config/
			$name . ".config.php";                         // openstreetmap.config.php

		if (file_exists($filepath)) {
			return $this->loadConfigFile($filepath);
		} else {
			return [];
		}
	}
}
