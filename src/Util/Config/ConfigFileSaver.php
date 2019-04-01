<?php

namespace Friendica\Util\Config;

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
	 * The settings array to save to
	 * @var array
	 */
	private $settings = [];

	/**
	 * Adds a given value to the config file
	 * Either it replaces the current value or it will get added
	 *
	 * @param string $cat   The configuration category
	 * @param string $key   The configuration key
	 * @param mixed  $value The new value
	 */
	public function addConfigValue($cat, $key, $value)
	{
		$settingsCount = count(array_keys($this->settings));

		for ($i = 0; $i < $settingsCount; $i++) {
			// if already set, overwrite the value
			if ($this->settings[$i]['cat'] === $cat &&
				$this->settings[$i]['key'] === $key) {
				$this->settings[$i] = ['cat' => $cat, 'key' => $key, 'value' => $value];
				return;
			}
		}

		$this->settings[] = ['cat' => $cat, 'key' => $key, 'value' => $value];
	}

	/**
	 * Resetting all added configuration entries so far
	 */
	public function reset()
	{
		$this->settings = [];
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
		// If no settings et, return true
		if (count(array_keys($this->settings)) === 0) {
			return true;
		}

		$saved = false;

		// Check for the *.config.php file inside the /config/ path
		$readLines = $this->getFile($this->getConfigFullName($name));
		if (!empty($readLines)) {
			$writeLines = $this->saveSettingsForConfig($readLines);
			// Close the current file handler and rename them
			if ($this->writeFile($this->getConfigFullName($name), $writeLines)) {
				// just return true, if everything went fine
				$saved = true;
			}
		}

		// Check for the *.ini.php file inside the /config/ path
		$readLines = $this->getFile($this->getIniFullName($name));
		if (!empty($readLines)) {
			$writeLines = $this->saveSettingsForINI($readLines);
			// Close the current file handler and rename them
			if ($this->writeFile($this->getIniFullName($name), $writeLines)) {
				// just return true, if everything went fine
				$saved = true;
			}
		}

		// Check for the *.php file (normally .htconfig.php) inside the / path
		$readLines = $this->getFile($this->getHtConfigFullName($name));
		if (!empty($readLines)) {
			$writeLines = $this->saveSettingsForLegacy($readLines);
			// Close the current file handler and rename them
			if ($this->writeFile($this->getHtConfigFullName($name), $writeLines)) {
				// just return true, if everything went fine
				$saved = true;
			}
		}

		$this->reset();

		return $saved;
	}

	/**
	 * Opens a config file and returns two handler for reading and writing
	 *
	 * @param string $fullName The full name of the current config
	 *
	 * @return array An array containing the file
	 */
	private function getFile($fullName)
	{
		if (empty($fullName)) {
			return [];
		}

		try {
			$readLines = file($fullName, FILE_IGNORE_NEW_LINES);
			return $readLines;
		} catch (\Exception $exception) {
			return [];
		}
	}

	/**
	 * Close and rename the config file
	 *
	 * @param string $fullName  The full name of the current config
	 * @param array  $writeFile The writing resource handler
	 *
	 * @return bool True, if the close was successful
	 */
	private function writeFile($fullName, array $writeFile)
	{
		try {
			if (!file_put_contents($fullName . '.tmp', implode(PHP_EOL, $writeFile))) {
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
	 * Save the added settings to a given array of config lines
	 * and return it as a new array
	 *
	 * @param array $readLines
	 *
	 * @return array
	 */
	private function saveSettingsForConfig(array $readLines)
	{
		$settingsCount = count(array_keys($this->settings));
		$categoryFound = array_fill(0, $settingsCount, false);
		$categoryBracketFound = array_fill(0, $settingsCount, false);
		$lineFound = array_fill(0, $settingsCount, false);
		$lineArrowFound = array_fill(0, $settingsCount, false);

		// check categories

		$writeLine = [];

		foreach ($readLines as $line) {

			// check for each added setting if we have to replace a config line
			for ($i = 0; $i < $settingsCount; $i++) {

				// find the first line like "'system' =>"
				if (!$categoryFound[$i] && stristr($line, sprintf('\'%s\'', $this->settings[$i]['cat']))) {
					$categoryFound[$i] = true;
				}

				// find the first line with a starting bracket ( "[" )
				if ($categoryFound[$i] && !$categoryBracketFound[$i] && stristr($line, '[')) {
					$categoryBracketFound[$i] = true;
				}

				// find the first line with the key like "'value'"
				if ($categoryBracketFound[$i] && !$lineFound[$i] && stristr($line, sprintf('\'%s\'', $this->settings[$i]['key']))) {
					$lineFound[$i] = true;
				}

				// find the first line with an arrow ("=>") after finding the key
				if ($lineFound[$i] && !$lineArrowFound[$i] && stristr($line, '=>')) {
					$lineArrowFound[$i] = true;
				}

				// find the current value and replace it
				if ($lineArrowFound[$i] && preg_match_all('/\'?(.*?)\'?/', $line, $matches, PREG_SET_ORDER)) {
					$lineVal = end($matches)[0];
					$line = str_replace($lineVal, $this->valueToString($this->settings[$i]['value']), $line);
					$categoryFound[$i] = false;
					$categoryBracketFound[$i] = false;
					$lineFound[$i] = false;
					$lineArrowFound[$i] = false;
					// if a line contains a closing bracket for the category ( "]" ) and we didn't find the key/value pair,
					// add it as a new line before the closing bracket
				} elseif ($categoryBracketFound[$i] && !$lineArrowFound[$i] && stristr($line, ']')) {
					$categoryFound[$i] = false;
					$categoryBracketFound[$i] = false;
					$lineFound[$i] = false;
					$lineArrowFound[$i] = false;
					$newLine = sprintf(self::INDENT . self::INDENT . '\'%s\' => %s,' . PHP_EOL, $this->settings[$i]['key'], $this->valueToString($this->settings[$i]['value']));
					$line = $newLine . $line;
				}
			}

			array_push($writeLine, $line);
		}

		return $writeLine;
	}

	/**
	 * Save the added settings to a given array of INI lines
	 * and return it as a new array
	 *
	 * @param array $readLines
	 *
	 * @return array
	 */
	private function saveSettingsForINI(array $readLines)
	{
		$settingsCount = count(array_keys($this->settings));
		$categoryFound = array_fill(0, $settingsCount, false);

		// find missing categories
		$missingCategories = array_reduce($this->settings, function ($matches, $setting) use ($readLines) {
			$category = $setting['cat'];
			if (!in_array(sprintf('[%s]', $category), $readLines) && (!isset($matches) || !in_array($category, $matches))) {
				$matches[] = $category;
			}

			return $matches;
		});

		// add missing categories and return as checkedLines
		$checkedLines = [];
		if (!empty($missingCategories)) {
			foreach ($readLines as $line) {
				if (preg_match_all('/^INI;.*$/', $line)) {
					foreach ($missingCategories as $category) {
						array_push($checkedLines, sprintf(PHP_EOL . '[%s]', $category));
					}
				}

				array_push($checkedLines, $line);
			}
		} else {
			$checkedLines = $readLines;
		}

		// check settings
		$writeLines = [];
		foreach ($checkedLines as $line) {

			// check for each added setting if we have to replace a config line
			for ($i = 0; $i < $settingsCount; $i++) {

				// find the category of the current setting
				if (
					!$categoryFound[$i] &&
					// right category found
					stristr($line, sprintf('[%s]', $this->settings[$i]['cat']))
				) {
					$categoryFound[$i] = true;

				// check the current value
				} elseif ($categoryFound[$i] &&
					// found the key in the right category
					preg_match_all('/^' . $this->settings[$i]['key'] . '\s*=\s*(.*?)$/', $line, $matches, PREG_SET_ORDER)
				) {
					$categoryFound[$i] = false;
					$line = sprintf('%s = %s', $this->settings[$i]['key'], $this->valueToString($this->settings[$i]['value'], true));

				// If end of INI file, add the line before the INI end
				} elseif (
					$categoryFound[$i] &&
					(
						// new category found
						preg_match_all('/^\s*?\[.*?\].*?$/', $line) ||
						// end of INI file found
						preg_match_all('/^\s*?INI;.*?$/', $line) ||
						// empty line found
						empty($line)
					)) {
					$categoryFound[$i] = false;
					array_push($writeLines, sprintf('%s = %s', $this->settings[$i]['key'], $this->valueToString($this->settings[$i]['value'], true)));
				}
			}

			array_push($writeLines, $line);
		}

		array_push($writeLines, PHP_EOL);

		return $writeLines;
	}

	/**
	 * Save the added settings to a given array of (legacy) config lines
	 * and return it as a new array
	 *
	 * @param array $readLines
	 *
	 * @return array
	 */
	private function saveSettingsForLegacy(array $readLines)
	{
		$settingsCount = count(array_keys($this->settings));
		$found  = array_fill(0, $settingsCount, false);

		$writeLines = [];

		foreach ($readLines as $line) {

			// check for each added setting if we have to replace a config line
			for ($i = 0; $i < $settingsCount; $i++) {

				// check for a non plain config setting (use category too)
				if ($this->settings[$i]['cat'] !== 'config' && preg_match_all('/^\$a\-\>config\[\'' . $this->settings[$i]['cat'] . '\'\]\[\'' . $this->settings[$i]['key'] . '\'\]\s*=\s*\'?(.*?)\'?;$/', $line, $matches, PREG_SET_ORDER)) {
					$line = '$a->config[\'' . $this->settings[$i]['cat'] . '\'][\'' . $this->settings[$i]['key'] . '\'] = ' . $this->valueToString($this->settings[$i]['value']) . ';';
					$found[$i] = true;

				// check for a plain config setting (don't use a category)
				} elseif ($this->settings[$i]['cat'] === 'config' && preg_match_all('/^\$a\-\>config\[\'' . $this->settings[$i]['key'] . '\'\]\s*=\s*\'?(.*?)\'?;$/', $line, $matches, PREG_SET_ORDER)) {
					$line = '$a->config[\'' . $this->settings[$i]['key'] . '\'] = ' . $this->valueToString($this->settings[$i]['value']) . ';';
					$found[$i] = true;
				}
			}

			array_push($writeLines, $line);
		}

		for ($i = 0; $i < $settingsCount; $i++) {
			if (!$found[$i]) {
				if ($this->settings[$i]['cat'] !== 'config') {
					$line = '$a->config[\'' . $this->settings[$i]['cat'] . '\'][\'' . $this->settings[$i]['key'] . '\'] = ' . $this->valueToString($this->settings[$i]['value']) . ';';
				} else {
					$line = '$a->config[\'' . $this->settings[$i]['key'] . '\'] = ' . $this->valueToString($this->settings[$i]['value']) . ';';
				}

				array_push($writeLines, $line);
			}
		}

		return $writeLines;
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
			case is_bool($value) && $value:
				return "true";
			case is_bool($value) && !$value:
				return "false";
			case is_array($value):
				if ($ini) {
					return implode(',', $value);
				} else {
					return "'" . implode(',', $value) . "'";
				}
			case is_numeric($value):
				return "$value";
			default:
				if ($ini) {
					return (string)$value;
				} else {
					return "'" . (string)$value . "'";
				}
		}
	}
}
