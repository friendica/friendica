<?php

namespace Friendica;

use Dice\Dice;
use Psr\Log\LoggerInterface;

/**
 * This class is capable of getting all dynamic created classes
 *
 * There has to be a "method" phpDoc for each new class, containing result class for a proper matching
 *
 * @method static App app()
 * @method static App\Authentication auth()
 * @method static App\Arguments args()
 * @method static App\BaseURL baseUrl()
 * @method static App\Mode mode()
 * @method static App\Module module()
 * @method static App\Page page()
 * @method static App\Router router()
 * @method static Content\Item contentItem()
 * @method static Content\Text\BBCode\Video bbCodeVideo()
 * @method static Core\Cache\ICache cache()
 * @method static Core\Config\IConfiguration config()
 * @method static Core\Config\IPConfiguration pConfig()
 * @method static Core\Lock\ILock lock()
 * @method static Core\L10n\L10n l10n()
 * @method static Core\Process process()
 * @method static Core\Session\ISession session()
 * @method static Database\Database dba()
 * @method static Model\Notify notify()
 * @method static Protocol\Activity activity()
 * @method static Util\ACLFormatter aclFormatter()
 * @method static Util\DateTimeFormat dtFormat()
 * @method static Util\FileSystem fs()
 * @method static Util\Profiler profiler()
 * @method static LoggerInterface logger()
 * @method static LoggerInterface devLogger()
 * @method static LoggerInterface workerLogger()
 *
 */
class DI
{
	const CLASS_MAPPING = [
		'app'          => App::class,
		'auth'         => App\Authentication::class,
		'args'         => App\Arguments::class,
		'baseUrl'      => App\BaseURL::class,
		'mode'         => App\Mode::class,
		'module'       => App\Module::class,
		'page'         => App\Page::class,
		'router'       => App\Router::class,
		'contentItem'  => Content\Item::class,
		'bbCodeVideo'  => Content\Text\BBCode\Video::class,
		'cache'        => Core\Cache\ICache::class,
		'config'       => Core\Config\IConfiguration::class,
		'pConfig'      => Core\Config\IPConfiguration::class,
		'l10n'         => Core\L10n\L10n::class,
		'lock'         => Core\Lock\ILock::class,
		'process'      => Core\Process::class,
		'session'      => Core\Session\ISession::class,
		'dba'          => Database\Database::class,
		'notify'       => Model\Notify::class,
		'activity'     => Protocol\Activity::class,
		'aclFormatter' => Util\ACLFormatter::class,
		'dtFormat'     => Util\DateTimeFormat::class,
		'fs'           => Util\FileSystem::class,
		'workerLogger' => Util\Logger\WorkerLogger::class,
		'profiler'     => Util\Profiler::class,
		'logger'       => LoggerInterface::class,
		'devLogger'    => '$devLogger',
	];

	/** @var Dice */
	private static $dice;

	public static function init(Dice $dice)
	{
		self::$dice = $dice;
	}

	public static function __callStatic($name, $arguments)
	{
		return self::$dice->create(self::CLASS_MAPPING[$name], $arguments);
	}
}
