<?php

namespace Friendica\Core\Config\Cache;

use Friendica\App;
use Friendica\Core\Addon;

/**
 * The ConfigCacheManager either loads config-files and stores them in a ConfigCache ( @see ConfigCache )
 * or saves specific variables back into the config-files
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigCacheManager
{
	/**
	 * The Sub directory of the config-files
	 * @var string
	 */
	const SUBDIRECTORY = 'config';
	/**
	 * The standard indention for config files
	 * @var string
	 */
	const IDENT = "\t";

	private $baseDir;
	private $configDir;

	/**
	 * @var App\Mode
	 */
	private $appMode;

	public function __construct($baseDir, App\Mode $mode)
	{
		$this->appMode = $mode;
		$this->baseDir = $baseDir;
		$this->configDir = $baseDir . DIRECTORY_SEPARATOR . self::SUBDIRECTORY;
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
		if (file_exists($this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php')) {
			return $this->loadConfigFile($this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php');
		} elseif (file_exists($this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php')) {
			return $this->loadINIConfigFile($this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php');
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
		$filePath = $this->baseDir  . DIRECTORY_SEPARATOR . '.' . $name . '.php';

		if (file_exists($filePath)) {
			$a = new \stdClass();
			$a->config = [];
			include $filePath;

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

	/**
	 * Saves a given value to the config file
	 * Either it replaces the current value or it will get added
	 *
	 * @param string $cat   The configuration category
	 * @param string $key   The configuration key
	 * @param string $value The new value
	 */
	public function saveToConfigFile($cat, $key, $value)
	{
		$this->saveToLegacyConfig('htpreconfig', $cat, $key, $value);
		$this->saveToLegacyConfig('htconfig', $cat, $key, $value);

		$this->saveToCoreConfig('local', $cat, $key, $value);
	}

	/**
	 * Saves a value to either an config or an ini file
	 *
	 * @param string $name  The configuration file name ('local', 'addon', ..)
	 * @param string $cat   The configuration category
	 * @param string $key   The configuration key
	 * @param string $value The new value
	 */
	private function saveToCoreConfig($name, $cat, $key, $value)
	{
		if (file_exists($this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php')) {
			$this->saveConfigFile($this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php', $cat, $key, $value);
		} elseif (file_exists($this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php')) {
			$this->saveINIConfigFile($this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php', $cat, $key, $value);
		} else {
			return;
		}
	}

	/**
	 * Saves a value to a config file
	 *
	 * @param string $fullName The configuration full name (including the path)
	 * @param string $cat   The configuration category
	 * @param string $key   The configuration key
	 * @param string $value The new value
	 */
	private function saveConfigFile($fullName, $cat, $key, $value)
	{
		$reading = fopen($fullName, 'r');
		$writing = fopen($fullName . '.tmp', 'w');

		$categoryFound = false;
		$categoryBracketFound = false;
		$lineFound = false;
		$lineArrowFound = false;

		while (!feof($reading)) {
			$line = fgets($reading);

			// find lines like '
			if (!$categoryFound && stristr($line, sprintf('\'%s\'', $cat))) {
				$categoryFound = true;
			}

			if ($categoryFound && !$categoryBracketFound && stristr($line, '[')) {
				$categoryBracketFound = true;
			}

			if ($categoryBracketFound && !$lineFound && stristr($line, sprintf('\'%s\'', $key))) {
				$lineFound = true;
			}

			if ($lineFound && !$lineArrowFound && stristr($line, '=>')) {
				$lineArrowFound = true;
			}

			if ($lineArrowFound && preg_match_all('/\'(.*?)\'/', $line, $matches, PREG_SET_ORDER)) {
				$lineVal = end($matches)[0];
				$writeLine = str_replace($lineVal, '\'' . $value . '\'', $line);
				$categoryFound = false;
				$categoryBracketFound = false;
				$lineFound = false;
				$lineArrowFound = false;
			} elseif ($categoryBracketFound && !$lineArrowFound && stristr($line, ']')) {
				$categoryFound = false;
				$categoryBracketFound = false;
				$lineFound = false;
				$lineArrowFound = false;
				$writeLine = sprintf(self::IDENT . self::IDENT .'\'%s\' => \'%s\',' . PHP_EOL, $key, $value);
				$writeLine .= $line;
			} else {
				$writeLine = $line;
			}

			fputs($writing, $writeLine);
		}

		fclose($reading);
		fclose($writing);
		rename($fullName . '.tmp', $fullName);
	}

	/**
	 * Saves a value to a ini file
	 *
	 * @param string $fullName The configuration full name (including the path)
	 * @param string $cat   The configuration category
	 * @param string $key   The configuration key
	 * @param string $value The new value
	 */
	private function saveINIConfigFile($fullName, $cat, $key, $value)
	{
		$reading = fopen($fullName, 'r');
		$writing = fopen($fullName . '.tmp', 'w');

		$categoryFound = false;

		while (!feof($reading)) {
			$line = fgets($reading);

			if (!$categoryFound && stristr($line, sprintf('[%s]', $cat))) {
				$categoryFound = true;
				$writeLine = $line;
			} elseif ($categoryFound && preg_match_all('/^' . $key . '\s*=\s*(.*?)$/', $line, $matches, PREG_SET_ORDER)) {
				$writeLine = $key . ' = ' . $value . PHP_EOL;
				$categoryFound = false;
			} elseif ($categoryFound && (preg_match_all('/^\[.*?\]$/', $line) || preg_match_all('/^INI;.*$/', $line))) {
				$categoryFound = false;
				$writeLine = $key . ' = ' .  $value . PHP_EOL;
				$writeLine .= $line;
			} else {
				$writeLine = $line;
			}

			fputs($writing, $writeLine);
		}

		fclose($reading);
		fclose($writing);
		rename($fullName . '.tmp', $fullName);
	}

	private function saveToLegacyConfig($name, $cat, $key, $value)
	{
		$filePath = $this->baseDir  . DIRECTORY_SEPARATOR . '.' . $name . '.php';

		if (!file_exists($filePath)) {
			return;
		}

		$reading = fopen($filePath, 'r');
		$writing = fopen($filePath . '.tmp', 'w');

		$found = false;

		while (!feof($reading)) {
			$line = fgets($reading);

			if (preg_match_all('/^\$a\-\>config\[\'' . $cat . '\',\'' . $key . '\'\]\s*=\s\'*(.*?)\'$/', $line, $matches, PREG_SET_ORDER)) {
				$writeLine = $key . ' = ' . $value . PHP_EOL;
				$found = true;
			} else {
				$writeLine = $line;
			}

			fputs($writing, $writeLine);
		}

		if (!$found) {
			$writeLine = $key . ' = ' . $value . PHP_EOL;
			fputs($writing, $writeLine);
		}

		fclose($reading);
		fclose($writing);
		rename($filePath . '.tmp', $fullName);
	}
}
