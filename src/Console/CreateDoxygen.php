<?php

namespace Friendica\Console;

/**
 * Description of CreateDoxygen
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class CreateDoxygen extends AbstractAppConsole
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

	protected function doExecute()
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

		foreach ($lines AS $line) {
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
	 * @brief Adds a doxygen header
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
			$space . " * @brief \n" .
			$space . " *\n"; /**/


		$left = strpos($line, "(");
		$line = substr($line, $left + 1);

		$right = strpos($line, ")");
		$line = trim(substr($line, 0, $right));

		if ($line != "") {
			$parameters = explode(",", $line);
			foreach ($parameters AS $parameter) {
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
