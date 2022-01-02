<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Special;

use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;

/**
 * This special module displays HTTPException when they are thrown in modules.
 *
 * @package Friendica\Module\Special
 */
class HTTPException
{
	/**
	 * Generates the necessary template variables from the caught HTTPException.
	 *
	 * Fills in the blanks if title or descriptions aren't provided by the exception.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 * @return array ['$title' => ..., '$description' => ...]
	 */
	private static function getVars(\Friendica\Network\HTTPException $e)
	{
		// Explanations are mostly taken from https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		$vars = [
			'$title' => $e->getDescription() ?: 'Error ' . $e->getCode(),
			'$message' => $e->getMessage() ?: $e->getExplanation(),
			'$back' => DI::l10n()->t('Go back'),
			'$stack_trace' => DI::l10n()->t('Stack trace:'),
		];

		if (DI::app()->isSiteAdmin()) {
			$vars['$thrown'] = DI::l10n()->t('Exception thrown in %s:%d', $e->getFile(), $e->getLine());
			$vars['$trace'] = $e->getTraceAsString();
		}

		return $vars;
	}

	/**
	 * Displays a bare message page with no theming at all.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 * @throws \Exception
	 */
	public function rawContent(\Friendica\Network\HTTPException $e)
	{
		$content = '';

		if ($e->getCode() >= 400) {
			$tpl = Renderer::getMarkupTemplate('http_status.tpl');
			$content = Renderer::replaceMacros($tpl, self::getVars($e));
		}

		System::httpExit($e->getCode(), $e->getDescription(), $content);
	}

	/**
	 * Returns a content string that can be integrated in the current theme.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 * @return string
	 * @throws \Exception
	 */
	public function content(\Friendica\Network\HTTPException $e): string
	{
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $e->getCode() . ' ' . $e->getDescription());

		if ($e->getCode() >= 400) {
			Logger::debug('Exit with error', ['code' => $e->getCode(), 'description' => $e->getDescription(), 'query' => DI::args()->getQueryString(), 'callstack' => System::callstack(20), 'method' => $_SERVER['REQUEST_METHOD'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
		}

		$tpl = Renderer::getMarkupTemplate('exception.tpl');

		return Renderer::replaceMacros($tpl, self::getVars($e));
	}
}
