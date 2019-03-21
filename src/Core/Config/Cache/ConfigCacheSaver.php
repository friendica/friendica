<?php

namespace Friendica\Core\Config\Cache;

/**
 * The ConfigCacheSaver saves specific variables back from the ConfigCache (@see ConfigCache )
 * into the config-files
 *
 * It is capable of loading the following config files:
 * - *.config.php   (current)
 * - *.ini.php      (deprecated)
 * - *.htconfig.php (deprecated)
 */
class ConfigCacheSaver extends ConfigCacheManager
{
	/**
	 * The standard indentation for config files
	 * @var string
	 */
	const INDENT = "\t";

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
		if (!empty($this->getConfigFullName($name))) {
			$this->saveConfigFile($this->getConfigFullName($name), $cat, $key, $value);
		} elseif (!empty($this->getIniFullName($name))) {
			$this->saveINIConfigFile($this->getIniFullName($name), $cat, $key, $value);
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
	 *
	 * @throws \Exception In case a file operation doesn't work
	 */
	private function saveConfigFile($fullName, $cat, $key, $value)
	{
		$reading = fopen($fullName, 'r');
		if (!$reading) {
			throw new \Exception('Cannot open config file \'' . $fullName . '\'.');
		}
		$writing = fopen($fullName . '.tmp', 'w');
		if (!$writing) {
			throw new \Exception('Cannot create temporary config file \'' . $fullName . '.tmp\'.');
		}

		$categoryFound = false;
		$categoryBracketFound = false;
		$lineFound = false;
		$lineArrowFound = false;

		while (!feof($reading)) {
			$line = fgets($reading);

			// find the first line like "'system' =>"
			if (!$categoryFound && stristr($line, sprintf('\'%s\'', $cat))) {
				$categoryFound = true;
			}

			// find the first line with a starting bracket ( "[" )
			if ($categoryFound && !$categoryBracketFound && stristr($line, '[')) {
				$categoryBracketFound = true;
			}

			// find the first line with the key like "'value'"
			if ($categoryBracketFound && !$lineFound && stristr($line, sprintf('\'%s\'', $key))) {
				$lineFound = true;
			}

			// find the first line with an arrow ("=>") after finding the key
			if ($lineFound && !$lineArrowFound && stristr($line, '=>')) {
				$lineArrowFound = true;
			}

			// find the current value and replace it
			if ($lineArrowFound && preg_match_all('/\'(.*?)\'/', $line, $matches, PREG_SET_ORDER)) {
				$lineVal = end($matches)[0];
				$writeLine = str_replace($lineVal, '\'' . $value . '\'', $line);
				$categoryFound = false;
				$categoryBracketFound = false;
				$lineFound = false;
				$lineArrowFound = false;

			// if a line contains a closing bracket for the category ( "]" ) and we didn't find the key/value pair,
			// add it as a new line before the closing bracket
			} elseif ($categoryBracketFound && !$lineArrowFound && stristr($line, ']')) {
				$categoryFound = false;
				$categoryBracketFound = false;
				$lineFound = false;
				$lineArrowFound = false;
				$writeLine = sprintf(self::INDENT . self::INDENT .'\'%s\' => \'%s\',' . PHP_EOL, $key, $value);
				$writeLine .= $line;
			} else {
				$writeLine = $line;
			}

			fputs($writing, $writeLine);
		}

		if (!fclose($reading)) {
			throw new \Exception('Cannot close config file \'' . $fullName . '\'.');
		};
		if (!fclose($writing)) {
			throw new \Exception('Cannot close temporary config file \'' . $fullName . '.tmp\'.');
		};
		if (!rename($fullName, $fullName . '.old')) {
			throw new \Exception('Cannot backup current config file \'' . $fullName . '\'.');
		}
		if (!rename($fullName . '.tmp', $fullName)) {
			throw new \Exception('Cannot move temporary config file \'' . $fullName . '.tmp\' to current.');
		}
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
		rename($fullName, $fullName . '.old');
		rename($fullName . '.tmp', $fullName);
	}

	private function saveToLegacyConfig($name, $cat, $key, $value)
	{
		if (empty($this->getHtConfigFullName($name))) {
			return;
		}

		$fullName = $this->getHtConfigFullName($name);

		$reading = fopen($fullName, 'r');
		$writing = fopen($fullName . '.tmp', 'w');

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
		rename($fullName, $fullName . '.old');
		rename($fullName . '.tmp', $fullName);
	}
}
