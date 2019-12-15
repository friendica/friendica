<?php

namespace Friendica;

use Dice\Dice;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Config\PConfiguration;
use Friendica\Core\L10n\L10n;
use Friendica\Core\Lock\ILock;
use Friendica\Core\Session\ISession;
use Friendica\Database\Database;
use Friendica\Model\Notify;
use Friendica\Protocol\Activity;
use Friendica\Util\ACLFormatter;
use Friendica\Content;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\FileSystem;
use Friendica\Util\Logger\WorkerLogger;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * This class is capable of getting all dynamic created classes
 *
 * There has to be a "method" phpDoc for each new class, containing result class for a proper matching
 *
 * @method static App app()
 * @method static ACLFormatter aclFormatter()
 * @method static Notify notify()
 * @method static Activity activity()
 * @method static Content\Item contentItem()
 * @method static Content\Text\BBCode\Video bbCodeVideo()
 * @method static DateTimeFormat dtFormat()
 * @method static ICache cache()
 * @method static Configuration config()
 * @method static PConfiguration pConfig()
 * @method static ILock lock()
 * @method static L10n l10n()
 * @method static LoggerInterface logger()
 * @method static LoggerInterface devLogger()
 * @method static LoggerInterface workerLogger()
 * @method static Profiler profiler()
 * @method static ISession session()
 * @method static App\Authentication auth()
 * @method static App\Arguments args()
 * @method static App\BaseURL baseUrl()
 * @method static App\Mode mode()
 * @method static App\Module module()
 * @method static App\Page page()
 * @method static App\Router router()
 * @method static Database dba()
 * @method static FileSystem fs()
 *
 */
class DI
{
	const CLASS_MAPPING = [
		'app'          => App::class,
		'aclFormatter' => ACLFormatter::class,
		'auth'         => App\Authentication::class,
		'args'         => App\Arguments::class,
		'baseUrl'      => App\BaseURL::class,
		'mode'         => App\Mode::class,
		'module'       => App\Module::class,
		'page'         => App\Page::class,
		'router'       => App\Router::class,
		'notify'       => Notify::class,
		'activity'     => Activity::class,
		'contentItem'  => Content\Item::class,
		'bbCodeVideo'  => Content\Text\BBCode\Video::class,
		'dtFormat'     => DateTimeFormat::class,
		'cache'        => ICache::class,
		'config'       => Configuration::class,
		'pConfig'      => PConfiguration::class,
		'l10n'         => L10n::class,
		'lock'         => ILock::class,
		'logger'       => LoggerInterface::class,
		'workerLogger' => WorkerLogger::class,
		'devLogger'    => '$devLogger',
		'session'      => ISession::class,
		'dba'          => Database::class,
		'fs'           => FileSystem::class,
		'profiler'     => Profiler::class,
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
