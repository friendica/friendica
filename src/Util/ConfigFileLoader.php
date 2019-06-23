<?php

namespace Friendica\Util;

use Exception;
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
class ConfigFileLoader
{
	/**
	 * The Sub directory of the config-files
	 *
	 * @var string
	 */
	const CONFIG_DIR = 'config';

	/**
	 * The Sub directory of the static config-files
	 *
	 * @var string
	 */
	const STATIC_DIR = 'static';

	/**
	 * The default name of the user defined ini file
	 *
	 * @var string
	 */
	const CONFIG_INI = 'local';

	/**
	 * The default name of the user defined legacy config file
	 *
	 * @var string
	 */
	const CONFIG_HTCONFIG = 'htconfig';

	/**
	 * The sample string inside the configs, which shouldn't get loaded
	 *
	 * @var string
	 */
	const SAMPLE_END = '-sample';

	/**
	 * @var App\Mode
	 */
	private $appMode;
	/**
	 * @var string
	 */
	private $baseDir;
	/**
	 * @var string
	 */
	private $configDir;
	/**
	 * @var string
	 */
	private $staticDir;

	public function __construct($baseDir, App\Mode $mode)
	{
		$this->baseDir   = $baseDir;
		$this->configDir = $baseDir . DIRECTORY_SEPARATOR . self::CONFIG_DIR;
		$this->staticDir = $baseDir . DIRECTORY_SEPARATOR . self::STATIC_DIR;
		$this->appMode   = $mode;
	}

	/**
	 * Load the configuration files into an configuration cache
	 *
	 * First loads the default value for all the configuration keys, then the legacy configuration files, then the
	 * expected local.config.php
	 *
	 * @param IConfigCache $config The config cache to load to
	 * @param bool         $raw    Setup the raw config format
	 *
	 * @throws Exception
	 */
	public function setupCache(IConfigCache $config, $raw = false)
	{
		// Load static config files first, the order is important
		$config->load($this->loadStaticConfig('defaults'));
		$config->load($this->loadStaticConfig('settings'));

		// try to load the legacy config first
		$config->load($this->loadLegacyConfig('htpreconfig'), true);
		$config->load($this->loadLegacyConfig('htconfig'), true);

		// Now load every other config you find inside the 'config/' directory
		$this->loadCoreConfig($config);

		// In case of install mode, add the found basepath (because there isn't a basepath set yet
		if (!$raw && ($this->appMode->isInstall() || empty($config->get('system', 'basepath')))) {
			// Setting at least the basepath we know
			$config->set('system', 'basepath', $this->baseDir);
		}
	}

	/**
	 * Tries to load the static core-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws Exception if the configuration file isn't readable
	 */
	private function loadStaticConfig($name)
	{
		$configName = $this->staticDir . DIRECTORY_SEPARATOR . $name . '.config.php';
		$iniName    = $this->staticDir . DIRECTORY_SEPARATOR . $name . '.ini.php';

		if (file_exists($configName)) {
			return $this->loadConfigFile($configName);
		} elseif (file_exists($iniName)) {
			return $this->loadINIConfigFile($iniName);
		} else {
			return [];
		}
	}

	/**
	 * Tries to load the specified core-configuration into the config cache.
	 *
	 * @param IConfigCache $config The Config cache
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws Exception if the configuration file isn't readable
	 */
	private function loadCoreConfig(IConfigCache $config)
	{
		// try to load legacy ini-files first
		foreach ($this->getConfigFiles(true) as $configFile) {
			$config->load($this->loadINIConfigFile($configFile), true);
		}

		// try to load supported config at last to overwrite it
		foreach ($this->getConfigFiles() as $configFile) {
			$config->load($this->loadConfigFile($configFile), true);
		}

		return [];
	}

	/**
	 * Tries to load the specified addon-configuration and returns the config array.
	 *
	 * @param string $name The name of the configuration
	 *
	 * @return array The config array (empty if no config found)
	 *
	 * @throws Exception if the configuration file isn't readable
	 */
	public function loadAddonConfig($name)
	{
		$filepath = $this->baseDir . DIRECTORY_SEPARATOR . // /var/www/html/
		            Addon::DIRECTORY . DIRECTORY_SEPARATOR . // addon/
		            $name . DIRECTORY_SEPARATOR . // openstreetmap/
		            self::CONFIG_DIR . DIRECTORY_SEPARATOR . // config/
		            $name . ".config.php";                         // openstreetmap.config.php

		if (file_exists($filepath)) {
			return $this->loadConfigFile($filepath);
		} else {
			return [];
		}
	}

	/**
	 * Get the config files of the config-directory
	 *
	 * @param bool $ini True, if scan for ini-files instead of config files
	 *
	 * @return array
	 */
	private function getConfigFiles(bool $ini = false)
	{
		$files = scandir($this->configDir);
		$found = array();

		$filePattern = ($ini ? '*.ini.php' : '*.config.php');

		// Don't load sample files
		$sampleEnd = self::SAMPLE_END . ($ini ? '.ini.php' : '.config.php');

		foreach ($files as $filename) {
			if (fnmatch($filePattern, $filename) && substr_compare($filename, $sampleEnd, -strlen($sampleEnd))) {
				$found[] = $this->configDir . '/' . $filename;
			}
		}

		return $found;
	}

	/**
	 * Tries to load the legacy config files (.htconfig.php, .htpreconfig.php) and returns the config array.
	 *
	 * @param string $name The name of the config file (default is empty, which means .htconfig.php)
	 *
	 * @return array The configuration array (empty if no config found)
	 *
	 * @deprecated since version 2018.09
	 */
	private function loadLegacyConfig($name = '')
	{
		$name     = !empty($name) ? $name : self::CONFIG_HTCONFIG;
		$fullName = $this->baseDir . DIRECTORY_SEPARATOR . '.' . $name . '.php';

		$config = [];
		if (file_exists($fullName)) {
			$a         = new \stdClass();
			$a->config = [];
			include $fullName;

			$htConfigCategories = array_keys($a->config);

			// map the legacy configuration structure to the current structure
			foreach ($htConfigCategories as $htConfigCategory) {
				if (is_array($a->config[$htConfigCategory])) {
					$keys = array_keys($a->config[$htConfigCategory]);

					foreach ($keys as $key) {
						$config[$htConfigCategory][$key] = $a->config[$htConfigCategory][$key];
					}
				} else {
					$config['config'][$htConfigCategory] = $a->config[$htConfigCategory];
				}
			}

			unset($a);

			if (isset($db_host)) {
				$config['database']['hostname'] = $db_host;
				unset($db_host);
			}
			if (isset($db_user)) {
				$config['database']['username'] = $db_user;
				unset($db_user);
			}
			if (isset($db_pass)) {
				$config['database']['password'] = $db_pass;
				unset($db_pass);
			}
			if (isset($db_data)) {
				$config['database']['database'] = $db_data;
				unset($db_data);
			}
			if (isset($config['system']['db_charset'])) {
				$config['database']['charset'] = $config['system']['db_charset'];
			}
			if (isset($pidfile)) {
				$config['system']['pidfile'] = $pidfile;
				unset($pidfile);
			}
			if (isset($default_timezone)) {
				$config['system']['default_timezone'] = $default_timezone;
				unset($default_timezone);
			}
			if (isset($lang)) {
				$config['system']['language'] = $lang;
				unset($lang);
			}
		}

		return $config;
	}

	/**
	 * Tries to load the specified legacy configuration file and returns the config array.
	 *
	 * @param string $filepath
	 *
	 * @return array The configuration array
	 * @throws Exception
	 * @deprecated since version 2018.12
	 */
	private function loadINIConfigFile($filepath)
	{
		$contents = include($filepath);

		$config = parse_ini_string($contents, true, INI_SCANNER_TYPED);

		if ($config === false) {
			throw new Exception('Error parsing INI config file ' . $filepath);
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
	 * @param string $filepath The filepath of the
	 *
	 * @return array The config array0
	 *
	 * @throws Exception if the config cannot get loaded.
	 */
	private function loadConfigFile($filepath)
	{
		$config = include($filepath);

		if (!is_array($config)) {
			throw new Exception('Error loading config file ' . $filepath);
		}

		return $config;
	}
}
