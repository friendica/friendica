<?php

namespace Friendica\Core\Console;

/**
 * Read a messages.po file and create strings.php in the same directory
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PoToPhp extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	const DQ_ESCAPE = "__DQ__";

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

		$pofile = realpath($this->getArgument(0));

		if (!file_exists($pofile)) {
			throw new \RuntimeException('Supplied file path doesn\'t exist.');
		}

		if (!is_writable(dirname($pofile))) {
			throw new \RuntimeException('Supplied directory isn\'t writable.');
		}

		$outfile = dirname($pofile) . DIRECTORY_SEPARATOR . 'strings.php';

		if (strstr($outfile, 'util')) {
			$lang = 'en';
		} else {
			$lang = str_replace('-', '_', basename(dirname($pofile)));
		}

		$this->out('Out to ' . $outfile);

		$out = "<?php\n\n";

		$infile = file($pofile);
		$k = '';
		$v = '';
		$arr = false;
		$ink = false;
		$inv = false;
		$escape_s_exp = '|[^\\\\]\$[a-z]|';

		foreach ($infile as $l) {
			$l = str_replace('\"', self::DQ_ESCAPE, $l);
			$len = strlen($l);
			if ($l[0] == "#") {
				$l = "";
			}

			if (substr($l, 0, 15) == '"Plural-Forms: ') {
				$match = [];
				preg_match("|nplurals=([0-9]*); *plural=(.*)[;\\\\]|", $l, $match);
				$cond = str_replace('n', '$n', $match[2]);
				// define plural select function if not already defined
				$fnname = 'string_plural_select_' . $lang;
				$out .= 'if(! function_exists("' . $fnname . '")) {' . "\n";
				$out .= 'function ' . $fnname . '($n){' . "\n";
				$out .= '	$n = intval($n);' . "\n";
				$out .= '	return ' . $cond . ';' . "\n";
				$out .= '}}' . "\n";
			}

			if ($k != '' && substr($l, 0, 7) == 'msgstr ') {
				if ($ink) {
					$ink = false;
					$out .= '$a->strings["' . $k . '"] = ';
				}

				if ($inv) {
					$out .= '"' . $v . '"';
				}

				$v = substr($l, 8, $len - 10);
				$v = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $v);

				$inv = true;
			}

			if ($k != "" && substr($l, 0, 7) == 'msgstr[') {
				if ($ink) {
					$ink = false;
					$out .= '$a->strings["' . $k . '"] = ';
				}
				if ($inv) {
					$inv = false;
					$out .= '"' . $v . '"';
				}

				if (!$arr) {
					$arr = true;
					$out .= "[\n";
				}

				$match = [];
				preg_match("|\[([0-9]*)\] (.*)|", $l, $match);
				$out .= "\t"
					. preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $match[1])
					. ' => '
					. preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $match[2])
					. ",\n";
			}

			if (substr($l, 0, 6) == 'msgid_') {
				$ink = false;
				$out .= '$a->strings["' . $k . '"] = ';
			}

			if ($ink) {
				$k .= trim($l, "\"\r\n");
				$k = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $k);
			}

			if (substr($l, 0, 6) == 'msgid ') {
				if ($inv) {
					$inv = false;
					$out .= '"' . $v . '"';
				}

				if ($k != "") {
					$out .= ($arr) ? "];\n" : ";\n";
				}

				$arr = false;
				$k = str_replace("msgid ", "", $l);
				if ($k != '""') {
					$k = trim($k, "\"\r\n");
				} else {
					$k = '';
				}

				$k = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $k);
				$ink = true;
			}

			if ($inv && substr($l, 0, 6) != "msgstr") {
				$v .= trim($l, "\"\r\n");
				$v = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $v);
			}
		}

		if ($inv) {
			$out .= '"' . $v . '"';
		}

		if ($k != '') {
			$out .= ($arr ? "];\n" : ";\n");
		}

		$out = str_replace(self::DQ_ESCAPE, '\"', $out);
		if (!file_put_contents($outfile, $out)) {
			throw new \RuntimeException('Unable to write to ' . $outfile);
		}

		return 0;
	}

	private function escapeDollar($match)
	{
		return str_replace('$', '\$', $match[0]);
	}
}
