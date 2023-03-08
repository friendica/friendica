<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Console;

use Friendica\Core\Config\Capability\IManageConfigValues;

/**
 * Tired of chasing typos and finding them after a commit.
 * Run this and quickly see if we've got any parse errors in our application files.
 */
class Typo extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var IManageConfigValues
	 */
	private $config;

	protected function getHelp()
	{
		$help = <<<HELP
console typo - Checks for parse errors in Friendica files
Usage
	bin/console typo [-h|--help|-?] [-v]

Description
	Checks all PHP files in the Friendica file tree for parse errors

Options
	-h|--help|-?  Show help information
	-v            Show more debug information.
HELP;
		return $help;
	}

	public function __construct(IManageConfigValues $config, array $argv = null)
	{
		parent::__construct($argv);

		$this->config = $config;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 0) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		$php_path = $this->config->get('config', 'php_path', 'php');

		if ($this->getOption('v')) {
			$this->out('Directory: src');
		}

		$Iterator = new \RecursiveDirectoryIterator('src');

		foreach (new \RecursiveIteratorIterator($Iterator) as $file) {
			if (substr($file, -4) === '.php') {
				$this->checkFile($php_path, $file);
			}
		}

		if ($this->getOption('v')) {
			$this->out('Directory: tests');
		}

		$Iterator = new \RecursiveDirectoryIterator('tests');

		foreach (new \RecursiveIteratorIterator($Iterator) as $file) {
			if (substr($file, -4) === '.php') {
				$this->checkFile($php_path, $file);
			}
		}

		if ($this->getOption('v')) {
			$this->out('Directory: mod');
		}

		$files = glob('mod/*.php');
		$this->checkFiles($php_path, $files);

		if ($this->getOption('v')) {
			$this->out('Directory: addon');
		}

		$dirs = glob('addon/*');
		foreach ($dirs as $dir) {
			$addon = basename($dir);
			$files = glob($dir . '/' . $addon . '.php');
			$this->checkFiles($php_path, $files);
		}

		if ($this->getOption('v')) {
			$this->out('String files');
		}

		$files = glob('view/lang/*/strings.php');
		$this->checkFiles($php_path, $files);

		$this->out('No errors.');

		return 0;
	}

	private function checkFiles($php_path, array $files)
	{
		foreach ($files as $file) {
			$this->checkFile($php_path, $file);
		}
	}

	private function checkFile($php_path, $file)
	{
		if ($this->getOption('v')) {
			$this->out('Checking ' . $file);
		}

		$output = [];
		$ret = 0;
		exec("$php_path -l $file", $output, $ret);
		if ($ret !== 0) {
			throw new \RuntimeException('Parse error found in ' . $file . ', scan stopped.');
		}
	}
}
