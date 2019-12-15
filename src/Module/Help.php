<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Text\Markdown;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Shows the friendica help based on the /doc/ directory
 */
class Help extends BaseModule
{
	public static function content(array $parameters = [])
	{
		Nav::setSelected('help');

		$text = '';
		$filename = '';

		$a = DI::app();
		$config = $a->getConfig();
		$lang = $config->get('system', 'language');

		// @TODO: Replace with parameter from router
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
			$text = self::loadDocFile('doc/' . $path . '.md', $lang);
			$a->page['title'] = L10n::t('Help:') . ' ' . str_replace('-', ' ', Strings::escapeTags($title));
		}

		$home = self::loadDocFile('doc/Home.md', $lang);
		if (!$text) {
			$text = $home;
			$filename = "Home";
			$a->page['title'] = L10n::t('Help');
		} else {
			$a->page['aside'] = Markdown::convert($home, false);
		}

		if (!strlen($text)) {
			throw new HTTPException\NotFoundException();
		}

		$html = Markdown::convert($text, false);

		if ($filename !== "Home") {
			// create TOC but not for home
			$lines = explode("\n", $html);
			$toc = "<h2>TOC</h2><ul id='toc'>";
			$lastLevel = 1;
			$idNum = [0, 0, 0, 0, 0, 0, 0];
			foreach ($lines as &$line) {
				$matches = [];
				foreach ($lines as &$line) {
					if (preg_match('#<h([1-6])>([^<]+?)</h\1>#i', $line, $matches)) {
						$level = $matches[1];
						$anchor = urlencode($matches[2]);
						if ($level < $lastLevel) {
							for ($k = $level; $k < $lastLevel; $k++) {
								$toc .= "</ul></li>";
							}

							for ($k = $level + 1; $k < count($idNum); $k++) {
								$idNum[$k] = 0;
							}
						}

						if ($level > $lastLevel) {
							$toc .= "<li><ul>";
						}

						$idNum[$level] ++;

						$href = $a->getBaseURL() . "/help/{$filename}#{$anchor}";
						$toc .= "<li><a href=\"{$href}\">" . strip_tags($line) . "</a></li>";
						$id = implode("_", array_slice($idNum, 1, $level));
						$line = "<a name=\"{$id}\"></a>" . $line;
						$line = "<a name=\"{$anchor}\"></a>" . $line;

						$lastLevel = $level;
					}
				}
			}

			for ($k = 0; $k < $lastLevel; $k++) {
				$toc .= "</ul>";
			}

			$html = implode("\n", $lines);

			$a->page['aside'] = '<div class="help-aside-wrapper widget"><div id="toc-wrapper">' . $toc . '</div>' . $a->page['aside'] . '</div>';
		}

		return $html;
	}

	private static function loadDocFile($fileName, $lang = 'en')
	{
		$baseName = basename($fileName);
		$dirName = dirname($fileName);
		if (file_exists("$dirName/$lang/$baseName")) {
			return file_get_contents("$dirName/$lang/$baseName");
		}

		if (file_exists($fileName)) {
			return file_get_contents($fileName);
		}

		return '';
	}
}
