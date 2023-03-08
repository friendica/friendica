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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Text\Markdown;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Shows the friendica help based on the /doc/ directory
 */
class Help extends BaseModule
{
	protected function content(array $request = []): string
	{
		Nav::setSelected('help');

		$text = '';
		$filename = '';

		$config = DI::config();
		$lang = DI::session()->get('language', $config->get('system', 'language'));

		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() > 1) {
			$path = '';
			// looping through the argv keys bigger than 0 to build
			// a path relative to /help
			for ($x = 1; $x < DI::args()->getArgc(); $x ++) {
				if (strlen($path)) {
					$path .= '/';
				}

				$path .= DI::args()->get($x);
			}
			$title = basename($path);
			$filename = $path;
			$text = self::loadDocFile('doc/' . $path . '.md', $lang);
			DI::page()['title'] = DI::l10n()->t('Help:') . ' ' . str_replace('-', ' ', $title);
		}

		$home = self::loadDocFile('doc/Home.md', $lang);
		if (!$text) {
			$text = $home;
			$filename = "Home";
			DI::page()['title'] = DI::l10n()->t('Help');
		} else {
			DI::page()['aside'] = Markdown::convert($home, false);
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

					$href = "help/{$filename}#{$anchor}";
					$toc .= "<li><a href=\"{$href}\">" . strip_tags($line) . "</a></li>";
					$id = implode("_", array_slice($idNum, 1, $level));
					$line = "<a name=\"{$id}\"></a>" . $line;
					$line = "<a name=\"{$anchor}\"></a>" . $line;

					$lastLevel = $level;
				}
			}

			for ($k = 0; $k < $lastLevel; $k++) {
				$toc .= "</ul>";
			}

			$html = implode("\n", $lines);

			DI::page()['aside'] = '<div class="help-aside-wrapper widget"><div id="toc-wrapper">' . $toc . '</div>' . DI::page()['aside'] . '</div>';
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
