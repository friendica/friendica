<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Logs;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class View extends BaseAdmin
{
	public const LIMIT      = 500;
	public const SCAN_LIMIT = 1000000;
	public const TIMEOUT    = 20;

	/** @var string */
	private $requestId;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Request $request, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->requestId = $request->getRequestId();
	}

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
			'context' => [
				'',
				LogChannel::APP,
				LogChannel::WORKER,
				LogChannel::DAEMON,
				LogChannel::JETSTREAM,
				LogChannel::CONSOLE,
				LogChannel::AUTH_JABBERED,
				LogChannel::DEV,
			],
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
					->withScanLimit(self::SCAN_LIMIT)
					->withTimeout(self::TIMEOUT)
					->withFilters($filters)
					->withSearch($search)
					->withExcludedRequestId($this->requestId);
			} catch (\Exception) {
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
				'Request_ID'            => DI::l10n()->t('Request ID'),
				'Worker_ID'             => DI::l10n()->t('Worker ID'),
				'Call_stack'            => DI::l10n()->t('Call stack'),
				'Close'                 => DI::l10n()->t('Close'),
				'Scan_limit_reached'    => DI::l10n()->t('Only the last %d lines of the log file have been searched.', self::SCAN_LIMIT),
				'Timeout_reached'       => DI::l10n()->t('The search has been stopped after %d seconds.', self::TIMEOUT),
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
