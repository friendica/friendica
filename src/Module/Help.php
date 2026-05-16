<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Text\Markdown;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Shows the friendica help based on the /doc/ directory
 */
class Help extends BaseModule
{
	protected function content(array $request = []): string
	{
		Nav::setSelected('help');

		$text     = '';
		$filename = '';

		$config = DI::config();
		$lang   = DI::session()->get('language', $config->get('system', 'language'));

		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() > 1) {
			$path = '';
			// looping through the argv keys bigger than 0 to build
			// a path relative to /help
			for ($x = 1; $x < DI::args()->getArgc(); $x++) {
				if (strlen($path)) {
					$path .= '/';
				}

				$path .= DI::args()->get($x);
			}
			$title              = Strings::ucFirst(basename($path));
			$filename           = $path;
			$text               = self::loadDocFile($path . '.md', $lang);
			DI::page()['title'] = DI::l10n()->t('Help:') . ' ' . str_replace('-', ' ', $title);
		}

		$home = self::loadDocFile('home.md', $lang);
		if (!$text) {
			$text               = $home;
			$filename           = "home";
			DI::page()['title'] = DI::l10n()->t('Help');
		} else {
			DI::page()['aside'] = Markdown::convert($home, false);
		}

		if (!strlen($text)) {
			throw new HTTPException\NotFoundException();
		}

		$html = Markdown::convert($text, false);

		if ($filename !== "home") {
			// create TOC but not for home
			$lines     = explode("\n", $html);
			$back_text = DI::l10n()->t('Home');
			$toc       = "<p><i class='fa fa-arrow-left'></i> <a href='/help'>&nbsp;$back_text</a></p><h2>TOC</h2><ul id='toc'>";
			$lastLevel = 1;
			$idNum     = [0, 0, 0, 0, 0, 0, 0];
			foreach ($lines as &$line) {
				$matches = [];
				if (preg_match('#<h([1-6])>([^<]+?)</h\1>#i', $line, $matches)) {
					$level  = $matches[1];
					$anchor = strtolower(urlencode($matches[2]));
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

					$idNum[$level]++;

					$href = "help/{$filename}#{$anchor}";
					$toc .= "<li><a href=\"{$href}\">" . strip_tags($line) . "</a></li>";
					$id   = implode("_", array_slice($idNum, 1, $level));
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

	private static function loadDocFile($filePath, $lang = 'en')
	{
		$baseDir = "doc";

		// Try loading docs inside a language dir first, then try English dir, then fall back to looking at the root dir
		$docPath = "$baseDir/$lang/$filePath";
		if (file_exists($docPath)) {
			return file_get_contents($docPath);
		}

		$docPath = "$baseDir/en/$filePath";
		if (file_exists($docPath)) {
			return file_get_contents($docPath);
		}

		// Delete this once database docs have been moved into en/spec/database
		$docPath = "$baseDir/$filePath";
		if (file_exists($docPath)) {
			return file_get_contents($docPath);
		}

		return '';
	}
}
