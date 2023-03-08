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

use Friendica\App;

/**
 * When I installed docblox, I had the experience that it does not generate any output at all.
 * This script may be used to find that kind of problems with the documentation build process.
 * If docblox generates output, use another approach for debugging.
 *
 * Basically, docblox takes a list of files to build documentation from. This script assumes there is a file or set of files
 * breaking the build when it is included in that list. It tries to calculate the smallest list containing these files.
 * Unfortunatly, the original problem is NP-complete, so what the script does is a best guess only.
 *
 * So it starts with a list of all files in the project.
 * If that list can't be build, it cuts it in two parts and tries both parts independently. If only one of them breaks,
 * it takes that one and tries the same independently. If both break, it assumes this is the smallest set. This assumption
 * is not necessarily true. Maybe the smallest set consists of two files and both of them were in different parts when
 * the list was divided, but by now it is my best guess. To make this assumption better, the list is shuffled after every step.
 *
 * After that, the script tries to remove a file from the list. It tests if the list breaks and if so, it
 * assumes that the file it removed belongs to the set of erroneous files.
 * This is done for all files, so, in the end removing one file leads to a working doc build.
 */
class DocBloxErrorChecker extends \Asika\SimpleConsole\Console
{

	protected $helpOptions = ['h', 'help', '?'];

	/** @var App */
	private $app;

	public function __construct(App $app, array $argv = null)
	{
		parent::__construct($argv);

		$this->app = $app;
	}

	protected function getHelp()
	{
		$help = <<<HELP
console docbloxerrorchecker - Checks the file tree for docblox errors
Usage
	bin/console docbloxerrorchecker [-h|--help|-?] [-v]

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
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

		if (!$this->commandExists('docblox')) {
			throw new \RuntimeException('DocBlox isn\'t available.');
		}

		$dir = $this->app->getBasePath();

		//stack for dirs to search
		$dirstack = [];
		//list of source files
		$filelist = [];

		//loop over all files in $dir
		while ($dh = opendir($dir)) {
			while ($file = readdir($dh)) {
				if (is_dir($dir . "/" . $file)) {
					//add to directory stack
					if (strpos($file, '.') !== 0) {
						array_push($dirstack, $dir . "/" . $file);
						$this->out('dir ' . $dir . '/' . $file);
					}
				} else {
					//test if it is a source file and add to filelist
					if (substr($file, strlen($file) - 4) == ".php") {
						array_push($filelist, $dir . "/" . $file);
						$this->out($dir . '/' . $file);
					}
				}
			}
			//look at the next dir
			$dir = array_pop($dirstack);
		}

		//check the entire set
		if ($this->runs($filelist)) {
			throw new \RuntimeException("I can not detect a problem.");
		}

		//check half of the set and discard if that half is okay
		$res = $filelist;
		$i = count($res);
		do {
			$this->out($i . '/' . count($filelist) . ' elements remaining.');
			$res = $this->reduce($res, count($res) / 2);
			shuffle($res);
			$i = count($res);
		} while (count($res) < $i);

		//check one file after another
		$needed = [];

		while (count($res) != 0) {
			$file = array_pop($res);

			if ($this->runs(array_merge($res, $needed))) {
				$this->out('needs: ' . $file . ' and file count ' . count($needed));
				array_push($needed, $file);
			}
		}

		$this->out('Smallest Set is: ' . $this->namesList($needed) . ' with ' . count($needed) . ' files. ');

		return 0;
	}

	private function commandExists($command)
	{
		$prefix = strpos(strtolower(PHP_OS),'win') > -1 ? 'where' : 'which';
		exec("{$prefix} {$command}", $output, $returnVal);
		return $returnVal === 0;
	}

	/**
	 * This function generates a comma separated list of file names.
	 *
	 * @param array $fileset Set of file names
	 *
	 * @return string comma-separated list of the file names
	 */
	private function namesList($fileset)
	{
		return implode(',', $fileset);
	}

	/**
	 * This functions runs phpdoc on the provided list of files
	 *
	 * @param array $fileset Set of filenames
	 *
	 * @return bool true, if that set can be built
	 */
	private function runs($fileset)
	{
		$fsParam = $this->namesList($fileset);
		$this->exec('docblox -t phpdoc_out -f ' . $fsParam);
		if (file_exists("phpdoc_out/index.html")) {
			$this->out('Subset ' . $fsParam . ' is okay.');
			$this->exec('rm -r phpdoc_out');
			return true;
		} else {
			$this->out('Subset ' . $fsParam . ' failed.');
			return false;
		}
	}

	/**
	 * This functions cuts down a fileset by removing files until it finally works.
	 * it was meant to be recursive, but php's maximum stack size is to small. So it just simulates recursion.
	 *
	 * In that version, it does not necessarily generate the smallest set, because it may not alter the elements order enough.
	 *
	 * @param array $fileset set of filenames
	 * @param int $ps number of files in subsets
	 *
	 * @return array a part of $fileset, that crashes
	 */
	private function reduce($fileset, $ps)
	{
		//split array...
		$parts = array_chunk($fileset, $ps);
		//filter working subsets...
		$parts = array_filter($parts, [$this, 'runs']);
		//melt remaining parts together
		if (is_array($parts)) {
			return array_reduce($parts, "array_merge", []);
		}
		return [];
	}

}
