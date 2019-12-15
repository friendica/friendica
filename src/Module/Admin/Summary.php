<?php

namespace Friendica\Module\Admin;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Update;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Register;
use Friendica\Module\BaseAdminModule;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\FileSystem;
use Friendica\Util\Network;

class Summary extends BaseAdminModule
{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		// are there MyISAM tables in the DB? If so, trigger a warning message
		$warningtext = [];
		if (DBA::count(['information_schema' => 'tables'], ['engine' => 'myisam', 'table_schema' => DBA::databaseName()])) {
			$warningtext[] = L10n::t('Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />', 'https://dev.mysql.com/doc/refman/5.7/en/converting-tables-to-innodb.html');
		}

		// Check if github.com/friendica/master/VERSION is higher then
		// the local version of Friendica. Check is opt-in, source may be master or devel branch
		if (Config::get('system', 'check_new_version_url', 'none') != 'none') {
			$gitversion = Config::get('system', 'git_friendica_version');
			if (version_compare(FRIENDICA_VERSION, $gitversion) < 0) {
				$warningtext[] = L10n::t('There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s', FRIENDICA_VERSION, $gitversion);
			}
		}

		if (Config::get('system', 'dbupdate', DBStructure::UPDATE_NOT_CHECKED) == DBStructure::UPDATE_NOT_CHECKED) {
			DBStructure::update($a->getBasePath(), false, true);
		}

		if (Config::get('system', 'dbupdate') == DBStructure::UPDATE_FAILED) {
			$warningtext[] = L10n::t('The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.');
		}

		if (Config::get('system', 'update') == Update::FAILED) {
			$warningtext[] = L10n::t('The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)');
		}

		$last_worker_call = Config::get('system', 'last_worker_execution', false);
		if (!$last_worker_call) {
			$warningtext[] = L10n::t('The worker was never executed. Please check your database structure!');
		} elseif ((strtotime(DateTimeFormat::utcNow()) - strtotime($last_worker_call)) > 60 * 60) {
			$warningtext[] = L10n::t('The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.', $last_worker_call);
		}

		// Legacy config file warning
		if (file_exists('.htconfig.php')) {
			$warningtext[] = L10n::t('Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.', $a->getBaseURL() . '/help/Config');
		}

		if (file_exists('config/local.ini.php')) {
			$warningtext[] = L10n::t('Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.', $a->getBaseURL() . '/help/Config');
		}

		// Check server vitality
		if (!self::checkSelfHostMeta()) {
			$well_known = $a->getBaseURL() . '/.well-known/host-meta';
			$warningtext[] = L10n::t('<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.',
				$well_known, $well_known, $a->getBaseURL() . '/help/Install');
		}

		// Check logfile permission
		if (Config::get('system', 'debugging')) {
			$file = Config::get('system', 'logfile');

			$fileSystem = DI::fs();

			try {
				$stream = $fileSystem->createStream($file);

				if (!isset($stream)) {
					throw new InternalServerErrorException('Stream is null.');
				}

			} catch (\Throwable $exception) {
				$warningtext[] = L10n::t('The logfile \'%s\' is not usable. No logging possible (error: \'%s\')', $file, $exception->getMessage());
			}

			$file = Config::get('system', 'dlogfile');

			try {
				if (!empty($file)) {
					$stream = $fileSystem->createStream($file);

					if (!isset($stream)) {
						throw new InternalServerErrorException('Stream is null.');
					}
				}

			} catch (\Throwable $exception) {
				$warningtext[] = L10n::t('The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')', $file, $exception->getMessage());
			}
		}

		// check legacy basepath settings
		$configLoader = new ConfigFileLoader($a->getBasePath());
		$configCache = new Config\Cache\ConfigCache();
		$configLoader->setupCache($configCache);
		$confBasepath = $configCache->get('system', 'basepath');
		$currBasepath = DI::config()->get('system', 'basepath');
		if ($confBasepath !== $currBasepath || !is_dir($currBasepath)) {
			if (is_dir($confBasepath) && Config::set('system', 'basepath', $confBasepath)) {
				DI::logger()->info('Friendica\'s system.basepath was updated successfully.', [
					'from' => $currBasepath,
					'to'   => $confBasepath,
				]);
				$warningtext[] = L10n::t('Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.',
					$currBasepath,
					$confBasepath);
			} elseif (!is_dir($currBasepath)) {
				DI::logger()->alert('Friendica\'s system.basepath is wrong.', [
					'from' => $currBasepath,
					'to'   => $confBasepath,
				]);
				$warningtext[] = L10n::t('Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.',
					$currBasepath,
					$confBasepath);
			} else {
				DI::logger()->alert('Friendica\'s system.basepath is wrong.', [
					'from' => $currBasepath,
					'to'   => $confBasepath,
				]);
				$warningtext[] = L10n::t('Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.',
					$currBasepath,
					$confBasepath);
			}
		}

		$accounts = [
			[L10n::t('Normal Account'), 0],
			[L10n::t('Automatic Follower Account'), 0],
			[L10n::t('Public Forum Account'), 0],
			[L10n::t('Automatic Friend Account'), 0],
			[L10n::t('Blog Account'), 0],
			[L10n::t('Private Forum Account'), 0]
		];

		$users = 0;
		$pageFlagsCountStmt = DBA::p('SELECT `page-flags`, COUNT(`uid`) AS `count` FROM `user` GROUP BY `page-flags`');
		while ($pageFlagsCount = DBA::fetch($pageFlagsCountStmt)) {
			$accounts[$pageFlagsCount['page-flags']][1] = $pageFlagsCount['count'];
			$users += $pageFlagsCount['count'];
		}
		DBA::close($pageFlagsCountStmt);

		Logger::log('accounts: ' . print_r($accounts, true), Logger::DATA);

		$pending = Register::getPendingCount();

		$deferred = DBA::count('workerqueue', ['NOT `done` AND `retrial` > ?', 0]);

		$workerqueue = DBA::count('workerqueue', ['NOT `done` AND `retrial` = ?', 0]);

		// We can do better, but this is a quick queue status
		$queues = ['label' => L10n::t('Message queues'), 'deferred' => $deferred, 'workerq' => $workerqueue];

		$variables = DBA::toArray(DBA::p('SHOW variables LIKE "max_allowed_packet"'));
		$max_allowed_packet = $variables ? $variables[0]['Value'] : 0;

		$server_settings = [
			'label' => L10n::t('Server Settings'),
			'php' => [
				'upload_max_filesize' => ini_get('upload_max_filesize'),
				'post_max_size' => ini_get('post_max_size'),
				'memory_limit' => ini_get('memory_limit')
			],
			'mysql' => [
				'max_allowed_packet' => $max_allowed_packet
			]
		];

		$t = Renderer::getMarkupTemplate('admin/summary.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Summary'),
			'$queues' => $queues,
			'$users' => [L10n::t('Registered users'), $users],
			'$accounts' => $accounts,
			'$pending' => [L10n::t('Pending registrations'), $pending],
			'$version' => [L10n::t('Version'), FRIENDICA_VERSION],
			'$platform' => FRIENDICA_PLATFORM,
			'$codename' => FRIENDICA_CODENAME,
			'$build' => Config::get('system', 'build'),
			'$addons' => [L10n::t('Active addons'), Addon::getEnabledList()],
			'$serversettings' => $server_settings,
			'$warningtext' => $warningtext
		]);
	}

	private static function checkSelfHostMeta()
	{
		// Fetch the host-meta to check if this really is a vital server
		return Network::curl(DI::app()->getBaseURL() . '/.well-known/host-meta')->isSuccess();
	}

}
