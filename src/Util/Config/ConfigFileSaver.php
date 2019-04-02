<?php

namespace Friendica\Util\Config;

use Friendica\Core\Config\Cache\IConfigCache;

/**
 * The ConfigFileSaver saves specific variables into the config-files
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigFileSaver extends ConfigFileManager
{
	/**
	 * The standard indentation for config files
	 * @var string
	 */
	const INDENT = "\t";

	/**
	 * @var IConfigCache
	 */
	private $configCache;

	public function __construct($baseDir, IConfigCache $configCache)
	{
		parent::__construct($baseDir);
		$this->configCache = $configCache;
	}

	/**
	 * Save all added configuration entries to the given config files
	 * After updating the config entries, all configuration entries will be reseted
	 *
	 * @param string $name The name of the configuration file (default is empty, which means the default name each type)
	 *
	 * @return bool true, if at least one configuration file was successfully updated or nothing to do
	 */
	public function saveToConfigFile($name = '')
	{
		if (empty($this->configCache->getAll())) {
			return true;
		}

		$saved = false;

		$configPath = $this->getConfigFullName($name);
		if (!empty($configPath) && is_file($configPath)) {
			$configFile  = file($configPath);
			$configCache = $this->loadConfigFile($configPath);
			$writeFile   = $this->saveConfigFile($configCache, $configFile);
			if ($this->saveToFile($configPath, $writeFile)) {
				$saved = true;
			}
		}

		// Check for the *.ini.php file inside the /config/ path
		$configPath = $this->getIniFullName($name);
		if (!empty($configPath) && is_file($configPath)) {
			$configFile  = file($configPath);
			$configCache = $this->loadINIConfigFile($configPath);
			$writeFile   = $this->saveINIConfigFile($configCache, $configFile);
			if ($this->saveToFile($configPath, $writeFile)) {
				$saved = true;
			}
		}

		// Check for the *.php file (normally .htconfig.php) inside the / path
		$configPath = $this->getHtConfigFullName($name);
		if (!empty($configPath) && is_file($configPath)) {
			$configFile  = file($configPath);
			$configCache = $this->loadLegacyConfig($name);
			$writeFile   = $this->saveLegacyConfig($configCache, $configFile);
			if ($this->saveToFile($configPath, $writeFile)) {
				$saved = true;
			}
		}

		return $saved;
	}

	private function saveToFile($fullName, array $config)
	{
		try {
			if (!file_put_contents($fullName . '.tmp', implode(PHP_EOL, $config))) {
				return false;
			}
		} catch (\Exception $exception) {
			return false;
		}

		try {
			$renamed = rename($fullName, $fullName . '.old');
		} catch (\Exception $exception) {
			return false;
		}

		if (!$renamed) {
			return false;
		}

		try {
			$renamed = rename($fullName . '.tmp', $fullName);
		} catch (\Exception $exception) {
			// revert the move of the current config file to have at least the old config
			rename($fullName . '.old', $fullName);
			return false;
		}

		if (!$renamed) {
			// revert the move of the current config file to have at least the old config
			rename($fullName . '.old', $fullName);
			return false;
		}

		return true;
	}

	/**
	 * Combines the config of the file with the config of the new settings
	 * Respects comments too
	 *
	 * @param array  $configCache
	 * @param string $configFile
	 *
	 * @return array
	 */
	private function saveConfigFile(array $configCache, $configFile)
	{
		$newConfigCache = $this->configCache->combine($configCache);
		$config = $newConfigCache->getAll();

		$newConfigArray = [
			'<?php',
			'',
			'',
			'return ['
		];

		foreach ($config as $category => $keys) {
			array_push($newConfigArray, sprintf(self::INDENT . '%s => [', $this->valueToString($category)));
			foreach ($keys as $key => $value) {
				array_push($newConfigArray, sprintf(self::INDENT . self::INDENT . '%s => %s,', $this->valueToString($key), $this->valueToString($value)));
			}
			array_push($newConfigArray, self::INDENT . '],');
		}

		array_push($newConfigArray, '];');

		return $newConfigArray;
	}

	/**
	 * Combines the config of the file with the config of the new settings
	 * Respects comments too
	 *
	 * @param array  $configCache
	 * @param string $configFile
	 *
	 * @return array
	 */
	private function saveINIConfigFile(array $configCache, $configFile)
	{
		$newConfigCache = $this->configCache->combine($configCache);
		$config = $newConfigCache->getAll();

		$newConfigArray = [
			'<?php',
			'',
			'',
			'return <<<INI',
			'',
		];

		foreach ($config as $category => $keys) {
			array_push($newConfigArray, sprintf('[%s]', $this->valueToString($category, true)));
			foreach ($keys as $key => $value) {
				array_push($newConfigArray, sprintf('%s = %s', $this->valueToString($key, true), $this->valueToString($value, true)));
			}
			array_push($newConfigArray, '');
		}

		array_push($newConfigArray, 'INI;');
		array_push($newConfigArray, '');

		return $newConfigArray;
	}

	/**
	 * Combines the config of the file with the config of the new settings
	 * Respects comments too
	 *
	 * @param array  $configCache
	 * @param string $configFile
	 *
	 * @return array
	 */
	private function saveLegacyConfig(array $configCache, $configFile)
	{
		$newConfigCache = $this->configCache->combine($configCache);
		$config = $newConfigCache->getAll();

		$newConfigArray = [
			'<?php',
			'',
		];

		foreach ($config as $category => $keys) {
			if ($category === 'config') {
				foreach ($keys as $key => $value) {
					array_push($newConfigArray, sprintf('$a->config[%s] = %s;', $this->valueToString($key), $this->valueToString($value)));
				}
			} else {
				foreach ($keys as $key => $value) {
					array_push($newConfigArray, sprintf('$a->config[%s][%s] = %s;', $this->valueToString($category), $this->valueToString($key), $this->valueToString($value)));
				}
			}

			array_push($newConfigArray, '');
		}

		return $newConfigArray;
	}

	/**
	 * creates a string representation of the current value
	 *
	 * @param mixed $value Any type of value
	 * @param bool  $ini   In case of ini, don't add apostrophes to values
	 *
	 * @return string
	 */
	private function valueToString($value, $ini = false)
	{
		switch (true) {
			case ((is_bool($value) && $value) ||
				$value === 'true'):
				return "true";
			case ((is_bool($value) && !$value) ||
				$value === 'false'):
				return "false";
			case is_array($value):
				if ($ini) {
					return implode(',', $value);
				} else {
					$array = '[';
					foreach ($value as $oneValue) {
						$array .= $this->valueToString($oneValue, $ini) . ',';
					}
					$array .= ']';
					return $array;
				}
			case is_numeric($value):
				return "$value";
			case defined($value):
				return "$value";
			default:
				if ($ini) {
					return (string)$value;
				} else {
					return "'" . addslashes((string)$value) . "'";
				}
		}
	}
}
