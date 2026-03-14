<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Geekwright\Po\PoFile;
use Geekwright\Po\PoTokens;

/**
 * Read a messages.po file and create strings.php in the same directory
 */
class PoToPhp extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	public const DQ_ESCAPE = "__DQ__";

	protected function getHelp()
	{
		$help = <<<HELP
console php2po - Generate a strings.php file from a messages.po file
Usage
	bin/console php2po <path/to/messages.po> [-h|--help|-?] [-v]

Description
	Read a messages.po file and create the according strings.php in the same directory

Options
	-h|--help|-?  Show help information
	-v            Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . self::class);
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

		$pofile = realpath($this->getArgument(0));

		if (!file_exists($pofile)) {
			throw new \RuntimeException('Supplied file path doesn\'t exist.');
		}

		if (!is_writable(dirname($pofile))) {
			throw new \RuntimeException('Supplied directory isn\'t writable.');
		}

		$outfile = dirname($pofile) . DIRECTORY_SEPARATOR . 'strings.php';

		if (basename(dirname($pofile)) == 'C') {
			$lang = 'en';
		} else {
			$lang = str_replace('-', '_', basename(dirname($pofile)));
		}

		$this->out('Out to ' . $outfile);

		$out = $this->poFile2Php($lang, $pofile);

		if (!file_put_contents($outfile, $out)) {
			throw new \RuntimeException('Unable to write to ' . $outfile);
		}

		return 0;
	}

	private function poFile2Php($lang, $infile): string
	{
		$poFile = new PoFile();
		$poFile->readPoFile($infile);

		$out = "<?php\n\n";

		$pluralForms = $poFile->getHeaderEntry()->getHeader('plural-forms');

		if (!$pluralForms) {
			throw new \RuntimeException('No Plural-Forms header detected');
		}

		$regex = 'nplurals=([0-9]*); *plural=(.*?)[\\\\;]';

		if (!preg_match('|' . $regex . '|', $pluralForms, $match)) {
			throw new \RuntimeException('Unexpected Plural-Forms header value, expected "' . $regex . '", found ' . $pluralForms);
		}

		$out .= $this->createPluralSelectFunctionString($match[2], $lang);

		foreach ($poFile->getEntries() as $entry) {
			if (!implode('', $entry->getAsStringArray(PoTokens::TRANSLATED))) {
				// Skip completely untranslated entries
				continue;
			}

			$out .= '$a->strings[' . self::escapePhpString($entry->getAsString(PoTokens::MESSAGE)) . '] = ';

			$msgid_plural = $entry->get(PoTokens::PLURAL);
			if (empty($msgid_plural)) {
				$out .= self::escapePhpString($entry->getAsString(PoTokens::TRANSLATED)) . ';' . "\n";
			} else {
				$out .= '[' . "\n";
				foreach ($entry->getAsStringArray(PoTokens::TRANSLATED) as $key => $msgstr) {
					$out .= "\t" . $key . ' => ' . self::escapePhpString($msgstr) . ',' . "\n";
				};

				$out .= '];' . "\n";
			}
		}

		return $out;
	}

	private function createPluralSelectFunctionString(string $pluralForms, string $lang): string
	{
		$return = $this->convertCPluralConditionToPhpReturnStatement(
			$pluralForms,
		);

		$fnname = 'string_plural_select_' . $lang;
		$out    = 'if(! function_exists("' . $fnname . '")) {' . "\n";
		$out .= 'function ' . $fnname . '($n){' . "\n";
		$out .= '	$n = intval($n);' . "\n";
		$out .= '	' . $return . "\n";
		$out .= '}}' . "\n";

		return $out;
	}

	private static function escapePhpString($string): string
	{
		return "'" . strtr($string, ['\'' => '\\\'']) . "'";
	}

	/**
	 * Converts C-style plural condition in .po files to a PHP-style plural return statement
	 *
	 * Adapted from https://github.com/friendica/friendica/issues/9747#issuecomment-769604485
	 * Many thanks to Christian Archer (https://github.com/sunchaserinfo)
	 *
	 * @param string $cond
	 * @return string
	 */
	private function convertCPluralConditionToPhpReturnStatement(string $cond)
	{
		$cond = str_replace('n', '$n', $cond);

		$tree = [];
		self::parse($cond, $tree);

		return is_string($tree) ? "return intval({$tree});" : self::render($tree);
	}

	/**
	 * Parses the condition into an array if there's at least a ternary operator, to a string otherwise
	 *
	 * Warning: Black recursive magic
	 *
	 * @param string $string
	 * @param array|string $node
	 */
	private static function parse(string $string, &$node)
	{
		// Removes extra outward parentheses
		if (strpos($string, '(') === 0 && strrpos($string, ')') === strlen($string) - 1) {
			$string = (string) substr($string, 1, -1);
		}

		$q = strpos($string, '?');
		$s = strpos($string, ':');

		if ($q === false && $s === false) {
			$node = $string;
			return;
		}

		if ($q === false || $s < $q) {
			[$then, $else] = explode(':', $string, 2);
			$node['then']  = $then;
			$parsedElse    = [];
			self::parse($else, $parsedElse);
			$node['else'] = $parsedElse;
		} else {
			[$if, $thenelse] = explode('?', $string, 2);
			$node['if']      = $if;
			self::parse($thenelse, $node);
		}
	}

	/**
	 * Renders the parsed condition tree into a return statement
	 *
	 * Warning: Black recursive magic
	 *
	 * @param $tree
	 * @return string
	 */
	private static function render($tree): string
	{
		if (is_array($tree)) {
			$if   = trim($tree['if']);
			$then = trim($tree['then']);
			$else = self::render($tree['else']);

			return "if ({$if}) { return {$then}; } else {$else}";
		}

		$tree = trim($tree);

		return " { return {$tree}; }";
	}
}
