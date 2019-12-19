<?php

namespace Friendica\Console;

use Friendica\Core\Config\IConfiguration;

/**
 * Tired of chasing typos and finding them after a commit.
 * Run this and quickly see if we've got any parse errors in our application files.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Typo extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var IConfiguration
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

	public function __construct(IConfiguration $config, array $argv = null)
	{
		parent::__construct($argv);

		$this->config = $config;
	}

	protected function doExecute()
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
			$this->out('Directory: include');
		}

		$files = glob('include/*.php');
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
