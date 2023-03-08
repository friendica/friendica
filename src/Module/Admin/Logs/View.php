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

namespace Friendica\Module\Admin\Logs;

use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Psr\Log\LogLevel;

class View extends BaseAdmin
{
	const LIMIT = 500;

	protected function content(array $request = []): string
	{
		parent::content();

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
			$error = DI::l10n()->t('Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.', $f);
		} else {
			try {
				$data = DI::parsedLogIterator()
						->open($f)
						->withLimit(self::LIMIT)
						->withFilters($filters)
						->withSearch($search);
			} catch (\Exception $e) {
				$error = DI::l10n()->t('Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.', $f);
			}
		}
		return Renderer::replaceMacros($t, [
			'$title'         => DI::l10n()->t('Administration'),
			'$page'          => DI::l10n()->t('View Logs'),
			'$l10n'          => [
				'Search'                => DI::l10n()->t('Search'),
				'Search_in_logs'        => DI::l10n()->t('Search in logs'),
				'Show_all'              => DI::l10n()->t('Show all'),
				'Date'                  => DI::l10n()->t('Date'),
				'Level'                 => DI::l10n()->t('Level'),
				'Context'               => DI::l10n()->t('Context'),
				'Message'               => DI::l10n()->t('Message'),
				'ALL'                   => DI::l10n()->t('ALL'),
				'View_details'          => DI::l10n()->t('View details'),
				'Click_to_view_details' => DI::l10n()->t('Click to view details'),
				'Event_details'         => DI::l10n()->t('Event details'),
				'Data'                  => DI::l10n()->t('Data'),
				'Source'                => DI::l10n()->t('Source'),
				'File'                  => DI::l10n()->t('File'),
				'Line'                  => DI::l10n()->t('Line'),
				'Function'              => DI::l10n()->t('Function'),
				'UID'                   => DI::l10n()->t('UID'),
				'Process_ID'            => DI::l10n()->t('Process ID'),
				'Close'                 => DI::l10n()->t('Close'),
			],
			'$data'          => $data,
			'$q'             => $search,
			'$filters'       => $filters,
			'$filtersvalues' => $filters_valid_values,
			'$error'         => $error,
			'$logname'       => DI::config()->get('system', 'logfile'),
		]);
	}
}
