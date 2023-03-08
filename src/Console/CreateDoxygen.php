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

/**
 * Description of CreateDoxygen
 */
class CreateDoxygen extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console createdoxygen - Generate Doxygen headers
Usage
	bin/console createdoxygen <file> [-h|--help|-?] [-v]

Description
	Outputs the provided file with added Doxygen headers to functions

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

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		$file = $this->getArgument(0);
		if (!file_exists($file)) {
			throw new \RuntimeException('Unable to find specified file.');
		}

		$data = file_get_contents($file);

		$lines = explode("\n", $data);

		$previous = "";

		foreach ($lines as $line) {
			$line = rtrim(trim($line, "\r"));

			if (strstr(strtolower($line), "function")) {
				$detect = strtolower(trim($line));
				$detect = implode(" ", explode(" ", $detect));

				$found = false;

				if (substr($detect, 0, 9) == "function ") {
					$found = true;
				}

				if (substr($detect, 0, 19) == "protected function ") {
					$found = true;
				}

				if (substr($detect, 0, 17) == "private function ") {
					$found = true;
				}

				if (substr($detect, 0, 23) == "public static function ") {
					$found = true;
				}

				if (substr($detect, 0, 24) == "private static function ") {
					$found = true;
				}

				if (substr($detect, 0, 10) == "function (") {
					$found = false;
				}

				if ($found && ( trim($previous) == "*/")) {
					$found = false;
				}

				if ($found) {
					$this->out($this->addDocumentation($line));
				}
			}
			$this->out($line);
			$previous = $line;
		}

		return 0;
	}

	/**
	 * Adds a doxygen header
	 *
	 * @param string $line The current line of the document
	 *
	 * @return string added doxygen header
	 */
	private function addDocumentation($line)
	{
		$trimmed = ltrim($line);
		$length = strlen($line) - strlen($trimmed);
		$space = substr($line, 0, $length);

		$block = $space . "/**\n" .
			$space . " * \n" .
			$space . " *\n"; /**/


		$left = strpos($line, "(");
		$line = substr($line, $left + 1);

		$right = strpos($line, ")");
		$line = trim(substr($line, 0, $right));

		if ($line != "") {
			$parameters = explode(",", $line);
			foreach ($parameters as $parameter) {
				$parameter = trim($parameter);
				$splitted = explode("=", $parameter);

				$block .= $space . " * @param " . trim($splitted[0], "& ") . "\n";
			}
			if (count($parameters) > 0) $block .= $space . " *\n";
		}

		$block .= $space . " * @return \n" .
			$space . " */\n";

		return $block;
	}

}
