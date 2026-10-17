<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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

		$config = DI::config();
		$lang   = DI::session()->get('language', $config->get('system', 'language')) ?: 'en';

		// @TODO: Replace with parameter from router
		// looping through the argv keys bigger than 0 to build a path relative to /help
		$path = '';
		for ($x = 1; $x < DI::args()->getArgc(); $x++) {
			if (strlen($path)) {
				$path .= '/';
			}

			$path .= DI::args()->get($x);
		}

		if ($path === '') {
			$filename           = 'home';
			DI::page()['title'] = DI::l10n()->t('Help');
		} else {
			$filename           = $path;
			$title              = Strings::ucFirst(basename($path));
			DI::page()['title'] = DI::l10n()->t('Help:') . ' ' . str_replace('-', ' ', $title);
		}

		// A document was requested explicitly. Falling back to the help home page
		// would answer every made up path with "200 OK" and the home page contents,
		// which lets crawlers walk an endless URL space.
		$docFile = self::findDocFile($filename . '.md', $lang);
		if ($docFile === null) {
			throw new HTTPException\NotFoundException();
		}

		$text = file_get_contents($docFile);

		if ($filename !== 'home') {
			$homeFile           = self::findDocFile('home.md', $lang);
			DI::page()['aside'] = $homeFile === null ? '' : $this->absoluteHelpLinks(Markdown::convert(file_get_contents($homeFile), false));
		}

		$html = $this->absoluteHelpLinks(Markdown::convert($text, false));

		if ($filename !== 'home') {
			// create TOC but not for home
			$helpUrl   = (string) $this->baseUrl . '/help';
			$lines     = explode("\n", $html);
			$back_text = DI::l10n()->t('Home');
			$toc       = "<p><i class='fa fa-arrow-left'></i> <a href='{$helpUrl}'>&nbsp;$back_text</a></p><h2>TOC</h2><ul id='toc'>";
			$lastLevel = 1;
			$idNum     = [0, 0, 0, 0, 0, 0, 0];
			foreach ($lines as &$line) {
				$matches = [];
				if (preg_match('#<h([1-6])>([^<]+?)</h\1>#i', $line, $matches)) {
					$level  = (int) $matches[1];
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

					$href = "{$helpUrl}/{$filename}#{$anchor}";
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

	/**
	 * The doc files link to each other relative to "/help", which only resolves on the
	 * /help page itself - on a sub page like /help/user/bbcode the browser resolves them
	 * to /help/user/help/user/... Make them absolute, based on the (possibly sub directory)
	 * base URL of this node.
	 */
	private function absoluteHelpLinks(string $html): string
	{
		return str_replace('href="help/', 'href="' . $this->baseUrl . '/help/', $html);
	}

	/**
	 * Returns the path of an existing doc file, or null when there is no such file
	 */
	private static function findDocFile(string $filePath, string $lang = 'en'): ?string
	{
		$baseDir = "doc";

		// Try loading docs inside a language dir first, then try English dir, then fall back to looking at the root dir
		$docPath = "$baseDir/$lang/$filePath";
		if (file_exists($docPath)) {
			return $docPath;
		}

		$docPath = "$baseDir/en/$filePath";
		if (file_exists($docPath)) {
			return $docPath;
		}

		// Delete this once database docs have been moved into en/spec/database
		$docPath = "$baseDir/$filePath";
		if (file_exists($docPath)) {
			return $docPath;
		}

		return null;
	}
}
