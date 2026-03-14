<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
				LogLevel::EMERGENCY,
				LogLevel::ALERT,
				LogLevel::CRITICAL,
				LogLevel::ERROR,
				LogLevel::WARNING,
				LogLevel::NOTICE,
				LogLevel::INFO,
				LogLevel::DEBUG,
			],
			'context' => ['', 'index', 'worker', 'daemon'],
		];
		$filters = [
			'level'   => $_GET['level']   ?? '',
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
			'$title' => DI::l10n()->t('Administration'),
			'$page'  => DI::l10n()->t('View Logs'),
			'$l10n'  => [
				'Search'                => DI::l10n()->t('Search in logs'),
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
			'$logname'       => DI::l10n()->t('Current log path: %s', DI::config()->get('system', 'logfile')),
		]);
	}
}
