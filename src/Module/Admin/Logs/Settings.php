<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Logs;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Psr\Log\LogLevel;

class Settings extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($_POST['page_logs'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/logs', 'admin_logs');

		$logfile   = (!empty($_POST['logfile']) ? trim($_POST['logfile']) : '');
		$debugging = !empty($_POST['debugging']);
		$loglevel  = ($_POST['loglevel'] ?? '') ?: LogLevel::ERROR;

		if (is_file($logfile) &&
		!is_writeable($logfile)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('The logfile \'%s\' is not writable. No logging possible', $logfile));
			return;
		}

		if (DI::config()->isWritable('system', 'logfile')) {
			DI::config()->set('system', 'logfile', $logfile);
		}
		if (DI::config()->isWritable('system', 'debugging')) {
			DI::config()->set('system', 'debugging', $debugging);
		}
		if (DI::config()->isWritable('system', 'loglevel')) {
			DI::config()->set('system', 'loglevel', $loglevel);
		}

		DI::baseUrl()->redirect('admin/logs');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$log_choices = [
			LogLevel::EMERGENCY => 'Emergency',
			LogLevel::ALERT     => 'Alert',
			LogLevel::CRITICAL  => 'Critical',
			LogLevel::ERROR     => 'Error',
			LogLevel::WARNING   => 'Warning',
			LogLevel::NOTICE    => 'Notice',
			LogLevel::INFO      => 'Info',
			LogLevel::DEBUG     => 'Debug',
		];

		if (ini_get('log_errors')) {
			$phplogenabled = DI::l10n()->t('PHP log currently enabled.');
		} else {
			$phplogenabled = DI::l10n()->t('PHP log currently disabled.');
		}

		$t = Renderer::getMarkupTemplate('admin/logs/settings.tpl');

		return Renderer::replaceMacros($t, [
			'$title'   => DI::l10n()->t('Administration'),
			'$page'    => DI::l10n()->t('Log settings'),
			'$submit'  => DI::l10n()->t('Save Settings'),
			'$clear'   => DI::l10n()->t('Clear'),
			'$logname' => DI::config()->get('system', 'logfile'),
			// see /help/smarty3-templates#1_1 on any Friendica node
			'$debugging'           => ['debugging', DI::l10n()->t('Enable Debugging'), DI::config()->get('system', 'debugging'), !DI::config()->isWritable('system', 'debugging') ? DI::l10n()->t('<strong>Read-only</strong> because it is set by an environment variable') : '', !DI::config()->isWritable('system', 'debugging') ? 'disabled' : ''],
			'$logfile'             => ['logfile', DI::l10n()->t('Log file'), DI::config()->get('system', 'logfile'), DI::l10n()->t('Must be writable by web server. Relative to your Friendica top-level directory.') . (!DI::config()->isWritable('system', 'logfile') ? '<br>' . DI::l10n()->t('<strong>Read-only</strong> because it is set by an environment variable') : ''), '', !DI::config()->isWritable('system', 'logfile') ? 'disabled' : ''],
			'$loglevel'            => ['loglevel', DI::l10n()->t("Log level"), DI::config()->get('system', 'loglevel'), !DI::config()->isWritable('system', 'loglevel') ? DI::l10n()->t('<strong>Read-only</strong> because it is set by an environment variable') : '', $log_choices, !DI::config()->isWritable('system', 'loglevel') ? 'disabled' : ''],
			'$form_security_token' => self::getFormSecurityToken("admin_logs"),
			'$phpheader'           => DI::l10n()->t("PHP logging"),
			'$phphint'             => DI::l10n()->t("To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the 'error_log' line is relative to the friendica top-level directory and must be writeable by the web server. The option '1' for 'log_errors' and 'display_errors' is to enable these options, set to '0' to disable them."),
			'$phplogcode'          => "error_reporting(E_ERROR | E_WARNING | E_PARSE);\nini_set('error_log','php.out');\nini_set('log_errors','1');\nini_set('display_errors', '1');",
			'$phplogenabled'       => $phplogenabled,
		]);
	}
}
