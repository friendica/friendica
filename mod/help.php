<?php
/**
 * @file mod/help.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Text;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;

function load_doc_file($s)
{
	$lang = Config::get('system', 'language');
	$b = basename($s);
	$d = dirname($s);
	if (file_exists("$d/$lang/$b")) {
		return file_get_contents("$d/$lang/$b");
	}

	if (file_exists($s)) {
		return file_get_contents($s);
	}

	return '';
}

function help_content(App $a)
{
	Nav::setSelected('help');

	$text = '';

	if ($a->argc > 1) {
		$path = '';
		// looping through the argv keys bigger than 0 to build
		// a path relative to /help
		for ($x = 1; $x < $a->argc; $x ++) {
			if (strlen($path)) {
				$path .= '/';
			}

			$path .= $a->getArgumentValue($x);
		}
		$title = basename($path);
		$filename = $path;
		$text = load_doc_file('doc/' . $path . '.md');
		$a->page['title'] = L10n::t('Help:') . ' ' . str_replace('-', ' ', Text::noTags($title));
	}

	$home = load_doc_file('doc/Home.md');
	if (!$text) {
		$text = $home;
		$filename = "Home";
		$a->page['title'] = L10n::t('Help');
	} else {
		$a->page['aside'] = Markdown::convert($home, false);
	}

	if (!strlen($text)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . L10n::t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		return replace_macros($tpl, [
			'$message' => L10n::t('Page not found.')
		]);
	}

	$html = Markdown::convert($text, false);

	if ($filename !== "Home") {
		// create TOC but not for home
		$lines = explode("\n", $html);
		$toc = "<h2>TOC</h2><ul id='toc'>";
		$lastlevel = 1;
		$idnum = [0, 0, 0, 0, 0, 0, 0];
		foreach ($lines as &$line) {
			if (substr($line, 0, 2) == "<h") {
				$level = substr($line, 2, 1);
				if ($level != "r") {
					$level = intval($level);
					if ($level < $lastlevel) {
						for ($k = $level; $k < $lastlevel; $k++) {
							$toc .= "</ul>";
						}

						for ($k = $level + 1; $k < count($idnum); $k++) {
							$idnum[$k] = 0;
						}
					}

					if ($level > $lastlevel) {
						$toc .= "<ul>";
					}

					$idnum[$level] ++;
					$id = implode("_", array_slice($idnum, 1, $level));
					$href = System::baseUrl() . "/help/{$filename}#{$id}";
					$toc .= "<li><a href='{$href}'>" . strip_tags($line) . "</a></li>";
					$line = "<a name='{$id}'></a>" . $line;
					$lastlevel = $level;
				}
			}
		}

		for ($k = 0; $k < $lastlevel; $k++) {
			$toc .= "</ul>";
		}

		$html = implode("\n", $lines);

		$a->page['aside'] = '<div class="help-aside-wrapper widget"><div id="toc-wrapper">' . $toc . '</div>' . $a->page['aside'] . '</div>';
	}

	return $html;
}
