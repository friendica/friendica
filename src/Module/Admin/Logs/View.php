<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module\Admin\Logs;

use Friendica\DI;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\Module\BaseAdmin;
use Friendica\Model\Log\ParsedLogIterator;
use Psr\Log\LogLevel;

class View extends BaseAdmin
{
	const LIMIT = 500;

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$t = Renderer::getMarkupTemplate('admin/logs/view.tpl');
		DI::page()->registerFooterScript(Theme::getPathForFile('js/module/admin/logs/view.js'));

		$f     = DI::config()->get('system', 'logfile');
		$data  = null;
		$error = null;

		$search = $_GET['q'] ?? '';

		$filters_valid_values = [
			'level' => [
				'',
				LogLevel::CRITICAL,
				LogLevel::ERROR,
				LogLevel::WARNING,
				LogLevel::NOTICE,
				LogLevel::INFO,
				LogLevel::DEBUG,
			],
			'context' => ['', 'index', 'worker'],
		];
		$filters = [
			'level'   => $_GET['level'] ?? '',
			'context' => $_GET['context'] ?? '',
		];
		foreach ($filters as $k => $v) {
			if ($v == '' || !in_array($v, $filters_valid_values[$k])) {
				unset($filters[$k]);
			}
		}

		if (!file_exists($f)) {
			$error = DI::l10n()->t('Error trying to open <strong>%1$s</strong> log file.\r\n<br/>Check to see if file %1$s exist and is readable.', $f);
		} else {
			try {
				$data = new ParsedLogIterator($f, self::LIMIT, $filters, $search);
			} catch (Exception $e) {
				$error = DI::l10n()->t('Couldn\'t open <strong>%1$s</strong> log file.\r\n<br/>Check to see if file %1$s is readable.', $f);
			}
		}
		return Renderer::replaceMacros($t, [
			'$title'         => DI::l10n()->t('Administration'),
			'$page'          => DI::l10n()->t('View Logs'),
			'$data'          => $data,
			'$q'             => $search,
			'$filters'       => $filters,
			'$filtersvalues' => $filters_valid_values,
			'$error'         => $error,
			'$logname'       => DI::config()->get('system', 'logfile'),
		]);
	}
}
