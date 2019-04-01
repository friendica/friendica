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
		$settingFound = array_fill(0, $settingsCount, false);

		// temporary create the full config to get multi-line patterns
		$fullConfig = implode(PHP_EOL, $readLines);

		// check categories
		// find missing categories
		$missingCategories = array_reduce($this->settings, function ($matches, $setting) use ($fullConfig) {
			$category = $setting['cat'];
			// match cases like "'category' => [", but also if there are comments, whitespaces or newlines in between
			if (!preg_match('/\s*\'?' . $category . '\'?[\s\w\*\/]*\=\>[\s\*\/\w]*\[/', $fullConfig)
				&&
				(!isset($matches) || !in_array($category, $matches))) {
				$matches[] = $category;
			}

			return $matches;
		});

		// add missing categories and return as checkedLines
		$checkedLines = [];
		if (!empty($missingCategories)) {
			foreach ($readLines as $line) {
				// check if it is the last line, and add the missing categories before
				if (preg_match_all('/\];/', $line)) {
					foreach ($missingCategories as $category) {
						array_push($checkedLines, sprintf(self::INDENT . '\'%s\' => [', $category));
						array_push($checkedLines, self::INDENT . '],');
					}
				}

				array_push($checkedLines, $line);
			}
		} else {
			$checkedLines = $readLines;
		}

		$returnFound = false;
		$returnBracketFound = false;
		$returnAdded = false;
		$currentCat = null;
		$currentCatOpenBracketFound = false;
		$currentCatCloseBracketFound = false;
		$currentCatAdded = false;
		$currentCatArrowFound = false;
		$currentKey = null;
		$currentKeyArrowFound = false;
		$currentVal = null;

		$writeLines = [];
		foreach ($checkedLines as $line) {

			// If we find a comment line, write the whole line and continue to the next line
			if (preg_match('/^\s*[\<\/\*]/', $line) ) {
				array_push($writeLines, $line);
				continue;
			}

			// split the current line in the different parts of the config file
			$values = preg_split("/(\ |\,)|\t/", $line, 0, PREG_SPLIT_NO_EMPTY);

			// skip empty values
			if (!empty($values)) {

				// loop through each part of the current line
				foreach ($values as $value) {

					// if we didn't found the return, nothing else will get triggered
					if (!$returnFound && $value === 'return') {
						$returnFound = true;
						continue;
					}

					// return is not enough, we need the [ as well (in case it's in the next line)
					if ($returnFound && !$returnBracketFound && $value === '[') {
						$returnBracketFound = true;
						continue;
					}

					// return is set, now check if we do have a new category (and not the end of array)
					if ($returnBracketFound && !isset($currentCat) && $value !== '];') {
						$currentCat = str_replace('\'', '', $value);
						continue;
					}

					// if we do have a category, the arrow of the category is necessary
					if (isset($currentCat) && !$currentCatArrowFound && $value === '=>') {
						$currentCatArrowFound = true;
						continue;
					}

					// if we do have the category arrow, we need the open bracket
					if ($currentCatArrowFound && !$currentCatOpenBracketFound && $value == '[') {
						$currentCatOpenBracketFound = true;
						continue;
					}

					// the open category bracket is found, check if the next value is the close bracket, so we
					// trigger the action for closing a category
					if ($currentCatOpenBracketFound && !$currentCatCloseBracketFound && $value === ']') {
						$currentCatCloseBracketFound = true;
						continue;
					}

					// the open category bracket is found and the next value is not a closing bracket
					// it has to be a config key
					if ($currentCatOpenBracketFound && !isset($currentKey) && $value !== ']') {
						$currentKey = str_replace('\'', '', $value);
						continue;
					}

					// we found a key, but we do need the arrow of the key as well
					if (isset($currentKey) && !$currentKeyArrowFound && $value === '=>') {
						$currentKeyArrowFound = true;
						continue;
					}

					// if we found the arrow of the key, the next value has to be the value of the key
					if ($currentKeyArrowFound && !isset($currentVal)) {
						$currentVal = str_replace('\'', '', $value);
						continue;
					}

					// we're still in the value setting mode, because all next values are values of the current key
					// (this is because we split by whitespace, which includes multi string values, e.g. "Friendica Server")
					if ($currentKeyArrowFound && isset($currentVal)) {
						$currentVal = ' ' . str_replace('\'', '', $value);
						continue;
					}
				}

				// if we didn't even found the return, just add the line
				if (!$returnFound) {
					array_push($writeLines, $line);
				}

				// if we found the return bracket, but didn't add the return line
				// add it now and check that it's just called once
				if ($returnBracketFound && !$returnAdded) {
					array_push($writeLines, 'return [');
					$returnAdded = true;
				}

				// if we found a category, but didn't add add it yet
				/// add it now and check that it's just called once
				if (isset($currentCat) && !$currentCatAdded) {
					array_push($writeLines, sprintf(self::INDENT . '%s => [', $this->valueToString($currentCat)));
					$currentCatAdded = true;
				}

				// if we found a value, check if we have to replace them by new settings
				if (isset($currentVal)) {

					// overwrite the current value in case we do have a new setting
					foreach ($this->settings as $num => $setting) {
						if ($setting['cat'] === $currentCat &&
							$setting['key'] === $currentKey) {

							$currentVal = $setting['value'];
							$settingFound[$num] = true;
						}
					}

					array_push($writeLines, sprintf(self::INDENT . self::INDENT . '%s => %s,', $this->valueToString($currentKey), $this->valueToString($currentVal)));

					// reset the flags for new key search
					$currentVal = null;
					$currentKey = null;
					$currentKeyArrowFound = false;
				}

				if ($currentCatCloseBracketFound) {

					// if the current category is closing, check if we have to add missing keys of the category
					foreach ($this->settings as $num => $setting) {

						if (!$settingFound[$num] &&
							$setting['cat'] === $currentCat) {

							array_push($writeLines, sprintf(self::INDENT . self::INDENT . '%s => %s,', $this->valueToString($setting['key']), $this->valueToString($setting['value'])));
							$settingFound[$num] = true;
						}
					}

					array_push($writeLines, self::INDENT . '],');

					// reset the flags for new category search
					$currentCat = null;
					$currentCatCloseBracketFound = false;
					$currentCatOpenBracketFound = false;
					$currentCatArrowFound = false;
					$currentCatAdded = false;
				}
			}
		}

		array_push($writeLines, '];');

		return $writeLines;
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

		$foundVarStart = false;
		$foundVarEnd = false;
		$bufferLine = '';

		foreach ($readLines as $line) {

			// If we find a comment line, write the whole line and continue to the next line
			if (preg_match('/^\s*[\/\*]/', $line) ) {
				array_push($writeLines, $line);
				continue;
			}

			// If we found start and end of a variable, set them as buffer line and activate replacing
			if (!$foundVarStart && preg_match('/\s*\$a/', $line) && preg_match('/.*\;$/', $line)) {
				$foundVarStart = true;
				$foundVarEnd = true;
				$bufferLine = $line;

			// If we found a start but not an end of a variable, start buffering the line until end is found
			} elseif (!$foundVarStart && preg_match('/\s*\$a/', $line) && !preg_match('/.*\;$/', $line)) {
				$foundVarStart = true;
				$bufferLine = $line;

			// If we find an end and we already found a start, add the line to the buffered line and activate replacing
			} elseif ($foundVarStart && preg_match('/.*\;$/', $line)) {
				$foundVarEnd = true;
				$bufferLine .= $line;

			// If we doesn't find an end, but we already found a start, add the line to the buffered line and continue
			} elseif($foundVarStart) {
				$bufferLine .= $line;

			// In any other case, just set the line as buffered line
			} else {
				$bufferLine = $line;
			}

			// In case we found the end of a variable setting, activate replacing
			if ($foundVarEnd) {
				// remove any unwanted tabs
				$bufferLine = preg_replace('/\t/', ' ', $bufferLine);
				// check for each added setting if we have to replace a config line
				for ($i = 0; $i < $settingsCount; $i++) {

					// check for a non plain config setting (use category too)
					if ($this->settings[$i]['cat'] !== 'config' && preg_match_all('/^\s*\$a\-\>config\s*\[\'' . $this->settings[$i]['cat'] . '\'\]\s*\[\'' . $this->settings[$i]['key'] . '\'\]\s*=\s*\'?(.*?)\'?;$/', $bufferLine, $matches, PREG_SET_ORDER)) {
						$bufferLine = '$a->config[\'' . $this->settings[$i]['cat'] . '\'][\'' . $this->settings[$i]['key'] . '\'] = ' . $this->valueToString($this->settings[$i]['value']) . ';';
						$found[$i] = true;

						// check for a plain config setting (don't use a category)
					} elseif ($this->settings[$i]['cat'] === 'config' && preg_match_all('/^\$a\-\>config\[\'' . $this->settings[$i]['key'] . '\'\]\s*=\s*\'?(.*?)\'?;$/', $bufferLine, $matches, PREG_SET_ORDER)) {
						$bufferLine = '$a->config[\'' . $this->settings[$i]['key'] . '\'] = ' . $this->valueToString($this->settings[$i]['value']) . ';';
						$found[$i] = true;
					}
				}

				// set both variables to false to save the buffered line to the array
				$foundVarEnd = false;
				$foundVarStart = false;
			}

			// if we are currently buffering, don't add the line to the array (we're not finished!)
			if (!$foundVarStart && !$foundVarEnd) {
				array_push($writeLines, $bufferLine);
			}
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
					return "'" . addslashes(implode(',', $value)) . "'";
				}
			case is_numeric($value):
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
