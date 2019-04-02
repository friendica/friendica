<?php

namespace Friendica\Util\Config;

/**
 * An abstract class in case of handling with config files
 */
abstract class ConfigFileManager
{
	/**
	 * The Sub directory of the config-files
	 * @var string
	 */
	const SUBDIRECTORY = 'config';

	/**
	 * The default name of the user defined config file
	 * @var string
	 */
	const CONFIG_LOCAL    = 'local';

	/**
	 * The default name of the user defined ini file
	 * @var string
	 */
	const CONFIG_INI      = 'local';

	/**
	 * The default name of the user defined legacy config file
	 * @var string
	 */
	const CONFIG_HTCONFIG = 'htconfig';

	protected $baseDir;
	protected $configDir;

	/**
	 * @param string $baseDir The base directory of Friendica
	 */
	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
		$this->configDir = $baseDir . DIRECTORY_SEPARATOR . self::SUBDIRECTORY;
	}

	/**
	 * Gets the full name (including the path) for a *.config.php (default is local.config.php)
	 *
	 * @param string $name The config name (default is empty, which means local.config.php)
	 *
	 * @return string The full name or empty if not found
	 */
	protected function getConfigFullName($name = '')
	{
		$name = !empty($name) ? $name : self::CONFIG_LOCAL;

		$fullName = $this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php';
		return file_exists($fullName) ? $fullName : '';
	}

	/**
	 * Gets the full name (including the path) for a *.ini.php (default is local.ini.php)
	 *
	 * @param string $name The config name (default is empty, which means local.ini.php)
	 *
	 * @return string The full name or empty if not found
	 */
	protected function getIniFullName($name = '')
	{
		$name = !empty($name) ? $name : self::CONFIG_INI;

		$fullName = $this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php';
		return file_exists($fullName) ? $fullName : '';
	}

	/**
	 * Gets the full name (including the path) for a .*.php (default is .htconfig.php)
	 *
	 * @param string $name The config name (default is empty, which means .htconfig.php)
	 *
	 * @return string The full name or empty if not found
	 */
	protected function getHtConfigFullName($name = '')
	{
		$name = !empty($name) ? $name : self::CONFIG_HTCONFIG;

		$fullName = $this->baseDir  . DIRECTORY_SEPARATOR . '.' . $name . '.php';
		return file_exists($fullName) ? $fullName : '';
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
	protected function loadLegacyConfig($name = '')
	{
		$config = [];
		if (!empty($this->getHtConfigFullName($name))) {
			$a = new \stdClass();
			$a->config = [];
			include $this->getHtConfigFullName($name);

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
	 * @deprecated since version 2018.12
	 * @param string $filepath
	 *
	 * @return array The configuration array
	 */
	protected function loadINIConfigFile($filepath)
	{
		$contents = include($filepath);

		$config = parse_ini_string($contents, true, INI_SCANNER_TYPED);

		if (!is_array($config)) {
			return [];
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
	 */
	protected function loadConfigFile($filepath)
	{
		$config = include($filepath);

		if (!is_array($config)) {
			return [];
		}

		return $config;
	}
}
