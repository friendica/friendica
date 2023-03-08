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
 * Read a strings.php file and create messages.po in the same directory
 */
class PhpToPo extends \Asika\SimpleConsole\Console
{

	protected $helpOptions = ['h', 'help', '?'];

	private $normBaseMsgIds = [];
	const NORM_REGEXP = "|[\\\]|";

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
console php2po - Generate a messages.po file from a strings.php file
Usage
	bin/console php2po [-p <n>] [--base <file>] <path/to/strings.php> [-h|--help|-?] [-v]

Description
	Read a strings.php file and create the according messages.po in the same directory

Options
	-p <n>        Number of plural forms. Default: 2
	--base <file> Path to base messages.po file. Default: view/lang/C/messages.po
	-h|--help|-?  Show help information
	-v            Show more debug information.
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

		$a = $this->app;

		$phpfile = realpath($this->getArgument(0));

		if (!file_exists($phpfile)) {
			throw new \RuntimeException('Supplied file path doesn\'t exist.');
		}

		if (!is_writable(dirname($phpfile))) {
			throw new \RuntimeException('Supplied directory isn\'t writable.');
		}

		$pofile = dirname($phpfile) . DIRECTORY_SEPARATOR . 'messages.po';

		// start !
		include_once($phpfile);

		$out = '';
		$out .= "# FRIENDICA Distributed Social Network\n";
		$out .= "# Copyright (C) 2010-2023, the Friendica project\n";
		$out .= "# This file is distributed under the same license as the Friendica package.\n";
		$out .= "# \n";
		$out .= 'msgid ""' . "\n";
		$out .= 'msgstr ""' . "\n";
		$out .= '"Project-Id-Version: friendica\n"' . "\n";
		$out .= '"Report-Msgid-Bugs-To: \n"' . "\n";
		$out .= '"POT-Creation-Date: ' . date("Y-m-d H:i:sO") . '\n"' . "\n";
		$out .= '"MIME-Version: 1.0\n"' . "\n";
		$out .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
		$out .= '"Content-Transfer-Encoding: 8bit\n"' . "\n";

		// search for plural info
		$lang = "";
		$lang_logic = "";
		$lang_pnum = $this->getOption('p', 2);

		$infile = file($phpfile);
		foreach ($infile as $l) {
			$l = trim($l);
			if ($this->startsWith($l, 'function string_plural_select_')) {
				$lang = str_replace('function string_plural_select_', '', str_replace('($n){', '', $l));
			}
			if ($this->startsWith($l, 'return')) {
				$lang_logic = str_replace('$', '', trim(str_replace('return ', '', $l), ';'));
				break;
			}
		}

		$this->out('Language: ' . $lang);
		$this->out('Plural forms: ' . $lang_pnum);
		$this->out('Plural forms: ' . $lang_logic);

		$out .= sprintf('"Language: %s\n"', $lang) . "\n";
		$out .= sprintf('"Plural-Forms: nplurals=%s; plural=%s;\n"', $lang_pnum, $lang_logic) . "\n";
		$out .= "\n";

		$base_path = $this->getOption('base', 'view/lang/C/messages.po');

		// load base messages.po and extract msgids
		$base_msgids = [];
		$base_f = file($base_path);
		if (!$base_f) {
			throw new \RuntimeException('The base ' . $base_path . ' file is missing or unavailable to read.');
		}

		$this->out('Loading base file ' . $base_path . '...');

		$_f = 0;
		$_mid = "";
		$_mids = [];
		foreach ($base_f as $l) {
			$l = trim($l);

			if ($this->startsWith($l, 'msgstr')) {
				if ($_mid != '""') {
					$base_msgids[$_mid] = $_mids;
					$this->normBaseMsgIds[preg_replace(self::NORM_REGEXP, "", $_mid)] = $_mid;
				}

				$_f = 0;
				$_mid = "";
				$_mids = [];
			}

			if ($this->startsWith($l, '"') && $_f == 2) {
				$_mids[count($_mids) - 1] .= "\n" . $l;
			}
			if ($this->startsWith($l, 'msgid_plural ')) {
				$_f = 2;
				$_mids[] = str_replace('msgid_plural ', '', $l);
			}

			if ($this->startsWith($l, '"') && $_f == 1) {
				$_mid .= "\n" . $l;
				$_mids[count($_mids) - 1] .= "\n" . $l;
			}
			if ($this->startsWith($l, 'msgid ')) {
				$_f = 1;
				$_mid = str_replace('msgid ', '', $l);
				$_mids = [$_mid];
			}
		}

		$this->out('Creating ' . $pofile . '...');

		// create msgid and msgstr
		$warnings = "";
		foreach ($a->strings as $key => $str) {
			$msgid = $this->massageString($key);

			if (preg_match("|%[sd0-9](\$[sn])*|", $msgid)) {
				$out .= "#, php-format\n";
			}
			$msgid = $this->findOriginalMsgId($msgid);
			$out .= 'msgid ' . $msgid . "\n";

			if (is_array($str)) {
				if (array_key_exists($msgid, $base_msgids) && isset($base_msgids[$msgid][1])) {
					$out .= 'msgid_plural ' . $base_msgids[$msgid][1] . "\n";
				} else {
					$out .= 'msgid_plural ' . $msgid . "\n";
					$warnings .= "[W] No source plural form for msgid:\n" . str_replace("\n", "\n\t", $msgid) . "\n\n";
				}
				foreach ($str as $n => $msgstr) {
					$out .= 'msgstr[' . $n . '] ' . $this->massageString($msgstr) . "\n";
				}
			} else {
				$out .= 'msgstr ' . $this->massageString($str) . "\n";
			}

			$out .= "\n";
		}

		if (!file_put_contents($pofile, $out)) {
			throw new \RuntimeException('Unable to write to ' . $pofile);
		}

		if ($warnings != '') {
			$this->out($warnings);
		}

		return 0;
	}

	private function startsWith($haystack, $needle)
	{
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	/**
	 * Get a string and retun a message.po ready text
	 * - replace " with \"
	 * - replace tab char with \t
	 * - manage multiline strings
	 *
	 * @param string $str
	 * @return string
	 */
	private function massageString($str)
	{
		$str = str_replace('\\', '\\\\', $str);
		$str = str_replace('"', '\"', $str);
		$str = str_replace("\t", '\t', $str);
		$str = str_replace("\n", '\n"' . "\n" . '"', $str);
		if (strpos($str, "\n") !== false && $str[0] !== '"') {
			$str = '"' . "\n" . $str;
		}

		$str = preg_replace("|\n([^\"])|", "\n\"$1", $str);
		return sprintf('"%s"', $str);
	}

	private function findOriginalMsgId($str)
	{
		$norm_str = preg_replace(self::NORM_REGEXP, "", $str);
		if (array_key_exists($norm_str, $this->normBaseMsgIds)) {
			return $this->normBaseMsgIds[$norm_str];
		}

		return $str;
	}

}
