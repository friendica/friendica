<?php

namespace Friendica\Core\Config\Cache;

use Friendica\App;
use Friendica\Core\Addon;

/**
 * The ConfigCacheLoader loads config-files and stores them in a ConfigCache ( @see ConfigCache )
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigCacheLoader extends ConfigCacheManager
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
	 * Load the configuration files
	 *
	 * First loads the default value for all the configuration keys, then the legacy configuration files, then the
	 * expected local.config.php
	 *
	 * @param IConfigCache The config cache to load to
	 *
	 * @throws \Exception
	 */
	public function loadConfigFiles(IConfigCache $config)
	{
		// Setting at least the basepath we know
		$config->set('system', 'basepath', $this->baseDir);

		$config->load($this->loadCoreConfig('defaults'));
		$config->load($this->loadCoreConfig('settings'));

		$config->load($this->loadLegacyConfig('htpreconfig'), true);
		$config->load($this->loadLegacyConfig('htconfig'), true);

		$config->load($this->loadCoreConfig('local'), true);

		// In case of install mode, add the found basepath (because there isn't a basepath set yet
		if ($this->appMode->isInstall()) {
			// Setting at least the basepath we know
			$config->set('system', 'basepath', $this->baseDir);
		}
	}

	/**
	 * Tries to load the specified core-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws \Exception if the configuration file isn't readable
	 */
	public function loadCoreConfig($name)
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
	 *
	 * @throws \Exception if the configuration file isn't readable
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

	/**
	 * Tries to load the legacy config files (.htconfig.php, .htpreconfig.php) and returns the config array.
	 *
	 * @param string $name The name of the config file
	 *
	 * @return array The configuration array (empty if no config found)
	 *
	 * @deprecated since version 2018.09
	 */
	private function loadLegacyConfig($name)
	{
		if ($this->getHtConfigFullName($name)) {
			$a = new \stdClass();
			$a->config = [];
			include $this->getHtConfigFullName($name);

			if (isset($db_host)) {
				$a->config['database']['hostname'] = $db_host;
				unset($db_host);
			}
			if (isset($db_user)) {
				$a->config['database']['username'] = $db_user;
				unset($db_user);
			}
			if (isset($db_pass)) {
				$a->config['database']['password'] = $db_pass;
				unset($db_pass);
			}
			if (isset($db_data)) {
				$a->config['database']['database'] = $db_data;
				unset($db_data);
			}
			if (isset($a->config['system']['db_charset'])) {
				$a->config['database']['charset'] = $a->config['system']['db_charset'];
			}
			if (isset($pidfile)) {
				$a->config['system']['pidfile'] = $pidfile;
				unset($pidfile);
			}
			if (isset($default_timezone)) {
				$a->config['system']['default_timezone'] = $default_timezone;
				unset($default_timezone);
			}
			if (isset($lang)) {
				$a->config['system']['language'] = $lang;
				unset($lang);
			}

			return $a->config;
		} else {
			return [];
		}
	}

	/**
	 * Tries to load the specified legacy configuration file and returns the config array.
	 *
	 * @deprecated since version 2018.12
	 * @param string $filepath
	 *
	 * @return array The configuration array
	 * @throws \Exception
	 */
	private function loadINIConfigFile($filepath)
	{
		$contents = include($filepath);

		$config = parse_ini_string($contents, true, INI_SCANNER_TYPED);

		if ($config === false) {
			throw new \Exception('Error parsing INI config file ' . $filepath);
		}

		return $config;
	}

	/**
	 * Tries to load the specified configuration file and returns the config array.
	 *
	 * The config format is PHP array and the template for configuration files is the following:
	 *
	 * <?php return [
	 *      'section' => [
	 *          'key' => 'value',
	 *      ],
	 * ];
	 *
	 * @param  string $filepath The filepath of the
	 * @return array The config array0
	 *
	 * @throws \Exception if the config cannot get loaded.
	 */
	private function loadConfigFile($filepath)
	{
		$config = include($filepath);

		if (!is_array($config)) {
			throw new \Exception('Error loading config file ' . $filepath);
		}

		return $config;
	}
}
