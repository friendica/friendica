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
use Friendica\Content\Item as ContentItem;
use Friendica\Content\Text\BBCode\Video as BBCodeVideo;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\FileSystem;
use Friendica\Util\Logger\WorkerLogger;
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
 * @method static ContentItem contentItem()
 * @method static BBCodeVideo bbCodeVideo()
 * @method static DateTimeFormat dtFormat()
 * @method static ICache cache()
 * @method static Configuration config()
 * @method static PConfiguration pConfig()
 * @method static ILock lock()
 * @method static L10n l10n()
 * @method static LoggerInterface logger()
 * @method static LoggerInterface devLogger()
 * @method static LoggerInterface workerLogger()
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
	/** @var Dice */
	private static $dice;

	public static function init(Dice $dice)
	{
		self::$dice = $dice;
	}

	public static function __callStatic($name, $arguments)
	{
		switch ($name) {
			case 'app':
				return self::$dice->create(App::class, $arguments);
			case 'aclFormatter':
				return self::$dice->create(ACLFormatter::class, $arguments);
			case 'auth':
				return self::$dice->create(App\Authentication::class, $arguments);
			case 'args':
				return self::$dice->create(App\Arguments::class, $arguments);
			case 'baseUrl':
				return self::$dice->create(App\BaseURL::class, $arguments);
			case 'mode':
				return self::$dice->create(App\Mode::class, $arguments);
			case 'module':
				return self::$dice->create(App\Module::class, $arguments);
			case 'page':
				return self::$dice->create(App\Page::class, $arguments);
			case 'router':
				return self::$dice->create(App\Router::class, $arguments);
			case 'notify':
				return self::$dice->create(Notify::class, $arguments);
			case 'activity':
				return self::$dice->create(Activity::class, $arguments);
			case 'contentItem':
				return self::$dice->create(ContentItem::class, $arguments);
			case 'bbCodeVideo':
				return self::$dice->create(BBCodeVideo::class, $arguments);
			case 'dtFormat':
				return self::$dice->create(DateTimeFormat::class, $arguments);
			case 'cache':
				return self::$dice->create(ICache::class, $arguments);
			case 'config':
				return self::$dice->create(Configuration::class, $arguments);
			case 'pConfig':
				return self::$dice->create(PConfiguration::class, $arguments);
			case 'lock':
				return self::$dice->create(ILock::class, $arguments);
			case 'l10n':
				return self::$dice->create(L10n::class, $arguments);
			case 'logger':
				return self::$dice->create(LoggerInterface::class, $arguments);
			case 'devLogger':
				return self::$dice->create('$devLogger', $arguments);
			case 'workerLogger':
				return self::$dice->create(WorkerLogger::class, $arguments);
			case 'session':
				return self::$dice->create(ISession::class, $arguments);
			case 'dba':
				return self::$dice->create(Database::class, $arguments);
			case 'fs':
				return self::$dice->create(FileSystem::class, $arguments);
			default:
				return null;
		}
	}
}
