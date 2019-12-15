<?php

namespace Friendica\Module\Admin\Logs;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Strings;
use Psr\Log\LogLevel;

class Settings extends BaseAdminModule
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		if (!empty($_POST['page_logs'])) {
			parent::checkFormSecurityTokenRedirectOnError('/admin/logs', 'admin_logs');

			$logfile   = (!empty($_POST['logfile']) ? Strings::escapeTags(trim($_POST['logfile'])) : '');
			$debugging = !empty($_POST['debugging']);
			$loglevel  = ($_POST['loglevel'] ?? '') ?: LogLevel::ERROR;

			if (is_file($logfile) &&
			!is_writeable($logfile)) {
				notice(L10n::t('The logfile \'%s\' is not writable. No logging possible', $logfile));
				return;
			}

			Config::set('system', 'logfile', $logfile);
			Config::set('system', 'debugging', $debugging);
			Config::set('system', 'loglevel', $loglevel);
		}

		info(L10n::t("Log settings updated."));
		DI::app()->internalRedirect('admin/logs');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$log_choices = [
			LogLevel::ERROR   => 'Error',
			LogLevel::WARNING => 'Warning',
			LogLevel::NOTICE  => 'Notice',
			LogLevel::INFO    => 'Info',
			LogLevel::DEBUG   => 'Debug',
		];

		if (ini_get('log_errors')) {
			$phplogenabled = L10n::t('PHP log currently enabled.');
		} else {
			$phplogenabled = L10n::t('PHP log currently disabled.');
		}

		$t = Renderer::getMarkupTemplate('admin/logs/settings.tpl');

		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Logs'),
			'$submit' => L10n::t('Save Settings'),
			'$clear' => L10n::t('Clear'),
			'$baseurl' => $a->getBaseURL(true),
			'$logname' => Config::get('system', 'logfile'),
			// see /help/smarty3-templates#1_1 on any Friendica node
			'$debugging' => ['debugging', L10n::t("Enable Debugging"), Config::get('system', 'debugging'), ""],
			'$logfile' => ['logfile', L10n::t("Log file"), Config::get('system', 'logfile'), L10n::t("Must be writable by web server. Relative to your Friendica top-level directory.")],
			'$loglevel' => ['loglevel', L10n::t("Log level"), Config::get('system', 'loglevel'), "", $log_choices],
			'$form_security_token' => parent::getFormSecurityToken("admin_logs"),
			'$phpheader' => L10n::t("PHP logging"),
			'$phphint' => L10n::t("To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the 'error_log' line is relative to the friendica top-level directory and must be writeable by the web server. The option '1' for 'log_errors' and 'display_errors' is to enable these options, set to '0' to disable them."),
			'$phplogcode' => "error_reporting(E_ERROR | E_WARNING | E_PARSE);\nini_set('error_log','php.out');\nini_set('log_errors','1');\nini_set('display_errors', '1');",
			'$phplogenabled' => $phplogenabled,
		]);
	}
}
