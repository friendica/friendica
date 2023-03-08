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

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

/**
 * Extracts translation strings from the Friendica project's files to be exported
 * to Transifex for translation.
 *
 * Outputs a PHP file with language strings used by Friendica
 */
class Extract extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console extract - Generate translation string file for the Friendica project (deprecated)
Usage
	bin/console extract [-h|--help|-?] [-v]

Description
	This script was used to generate the translation string file to be exported to Transifex,
	please use bin/run_xgettext.sh instead

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

		$s = '<?php' . PHP_EOL;
		$s .= '
		function string_plural_select($n){
			return ($n != 1);
		}

		';

		$arr = [];

		$files = array_merge(
			['index.php'],
			glob('mod/*'),
			glob('addon/*/*'),
			$this->globRecursive('src')
		);

		foreach ($files as $file) {
			$str = file_get_contents($file);

			$pat = '|->t\(([^\)]*+)[\)]|';
			$patt = '|->tt\(([^\)]*+)[\)]|';

			$matches = [];
			$matchestt = [];

			preg_match_all($pat, $str, $matches);
			preg_match_all($patt, $str, $matchestt);

			if (count($matches) || count($matchestt)) {
				$s .= '// ' . $file . PHP_EOL;
			}

			if (!empty($matches[1])) {
				foreach ($matches[1] as $long_match) {
					$match_arr = preg_split('/(?<=[\'"])\s*,/', $long_match);
					$match = $match_arr[0];
					if (!in_array($match, $arr)) {
						if (substr($match, 0, 1) == '$') {
							continue;
						}

						$arr[] = $match;

						$s .= '$a->strings[' . $match . '] = ' . $match . ';' . "\n";
					}
				}
			}
			if (!empty($matchestt[1])) {
				foreach ($matchestt[1] as $match) {
					$matchtkns = preg_split("|[ \t\r\n]*,[ \t\r\n]*|", $match);
					if (count($matchtkns) == 3 && !in_array($matchtkns[0], $arr)) {
						if (substr($matchtkns[1], 0, 1) == '$') {
							continue;
						}

						$arr[] = $matchtkns[0];

						$s .= '$a->strings[' . $matchtkns[0] . "] = [\n";
						$s .= "\t0 => " . $matchtkns[0] . ",\n";
						$s .= "\t1 => " . $matchtkns[1] . ",\n";
						$s .= "];\n";
					}
				}
			}
		}

		$s .= '// Timezones' . PHP_EOL;

		$zones = timezone_identifiers_list();
		foreach ($zones as $zone) {
			$s .= '$a->strings[\'' . $zone . '\'] = \'' . $zone . '\';' . "\n";
		}

		$this->out($s);

		return 0;
	}

	/**
	 * Returns an array with found files and directories including their paths.
	 *
	 * @param string $path Base path to scan
	 *
	 * @return array A flat array with found files and directories
	 */
	private function globRecursive(string $path): array
	{
		$dir_iterator = new RecursiveDirectoryIterator($path);
		$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

		$return = [];
		foreach ($iterator as $file) {
			if ($file->getBasename() != '.' && $file->getBasename() != '..') {
				$return[] = $file->getPathname();
			}
		}

		return $return;
	}
}
