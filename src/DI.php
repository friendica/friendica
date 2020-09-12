<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica;

use Dice\Dice;
use Psr\Log\LoggerInterface;

/**
 * This class is capable of getting all dynamic created classes
 *
 * @see https://designpatternsphp.readthedocs.io/en/latest/Structural/Registry/README.html
 */
abstract class DI
{
	/** @var Dice */
	private static $dice;

	public static function init(Dice $dice)
	{
		self::$dice = $dice;
	}

	//
	// common instances
	//

	/**
	 * @return App
	 */
	public static function app()
	{
		return self::$dice->create(App::class);
	}

	/**
	 * @return Database\Database
	 */
	public static function dba()
	{
		return self::$dice->create(Database\Database::class);
	}

	//
	// "App" namespace instances
	//

	/**
	 * @return App\Authentication
	 */
	public static function auth()
	{
		return self::$dice->create(App\Authentication::class);
	}

	/**
	 * @return App\Arguments
	 */
	public static function args()
	{
		return self::$dice->create(App\Arguments::class);
	}

	/**
	 * @return App\BaseURL
	 */
	public static function baseUrl()
	{
		return self::$dice->create(App\BaseURL::class);
	}

	/**
	 * @return App\Mode
	 */
	public static function mode()
	{
		return self::$dice->create(App\Mode::class);
	}

	/**
	 * @return App\Module
	 */
	public static function module()
	{
		return self::$dice->create(App\Module::class);
	}

	/**
	 * @return App\Page
	 */
	public static function page()
	{
		return self::$dice->create(App\Page::class);
	}

	/**
	 * @return App\Router
	 */
	public static function router()
	{
		return self::$dice->create(App\Router::class);
	}

	//
	// "Content" namespace instances
	//

	/**
	 * @return Content\Item
	 */
	public static function contentItem()
	{
		return self::$dice->create(Content\Item::class);
	}

	/**
	 * @return Content\Text\BBCode\Video
	 */
	public static function bbCodeVideo()
	{
		return self::$dice->create(Content\Text\BBCode\Video::class);
	}

	//
	// "Core" namespace instances
	//

	/**
	 * @return Core\Cache\ICache
	 */
	public static function cache()
	{
		return self::$dice->create(Core\Cache\ICache::class);
	}

	/**
	 * @return Core\Config\IConfig
	 */
	public static function config()
	{
		return self::$dice->create(Core\Config\IConfig::class);
	}

	/**
	 * @return Core\PConfig\IPConfig
	 */
	public static function pConfig()
	{
		return self::$dice->create(Core\PConfig\IPConfig::class);
	}

	/**
	 * @return Core\Lock\ILock
	 */
	public static function lock()
	{
		return self::$dice->create(Core\Lock\ILock::class);
	}

	/**
	 * @return Core\L10n
	 */
	public static function l10n()
	{
		return self::$dice->create(Core\L10n::class);
	}

	/**
	 * @return Core\Process
	 */
	public static function process()
	{
		return self::$dice->create(Core\Process::class);
	}

	/**
	 * @return Core\Session\ISession
	 */
	public static function session()
	{
		return self::$dice->create(Core\Session\ISession::class);
	}

	/**
	 * @return Core\StorageManager
	 */
	public static function storageManager()
	{
		return self::$dice->create(Core\StorageManager::class);
	}

	//
	// "LoggerInterface" instances
	//

	/**
	 * @return LoggerInterface
	 */
	public static function logger()
	{
		return self::$dice->create(LoggerInterface::class);
	}

	/**
	 * @return LoggerInterface
	 */
	public static function devLogger()
	{
		return self::$dice->create('$devLogger');
	}

	/**
	 * @return LoggerInterface
	 */
	public static function workerLogger()
	{
		return self::$dice->create(Util\Logger\WorkerLogger::class);
	}

	//
	// "Factory" namespace instances
	//

	/**
	 * @return \Friendica\Domain\Factory\Api\Mastodon\Account
	 */
	public static function mstdnAccount()
	{
		return self::$dice->create(Domain\Factory\Api\Mastodon\Account::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Api\Mastodon\Emoji
	 */
	public static function mstdnEmoji()
	{
		return self::$dice->create(Domain\Factory\Api\Mastodon\Emoji::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Api\Mastodon\Field
	 */
	public static function mstdnField()
	{
		return self::$dice->create(Domain\Factory\Api\Mastodon\Field::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Api\Mastodon\FollowRequest
	 */
	public static function mstdnFollowRequest()
	{
		return self::$dice->create(Domain\Factory\Api\Mastodon\FollowRequest::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Api\Mastodon\Relationship
	 */
	public static function mstdnRelationship()
	{
		return self::$dice->create(Domain\Factory\Api\Mastodon\Relationship::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Api\Mastodon\Status
	 */
	public static function mstdnStatus()
	{
		return self::$dice->create(Domain\Factory\Api\Mastodon\Status::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Api\Twitter\User
	 */
	public static function twitterUser()
	{
		return self::$dice->create(Domain\Factory\Api\Twitter\User::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Notification\Notification
	 */
	public static function notification()
	{
		return self::$dice->create(Domain\Factory\Notification\Notification::class);
	}

	/**
	 * @return \Friendica\Domain\Factory\Notification\Introduction
	 */
	public static function notificationIntro()
	{
		return self::$dice->create(Domain\Factory\Notification\Introduction::class);
	}

	//
	// "Model" namespace instances
	//

	/**
	 * @return Model\User\Cookie
	 */
	public static function cookie()
	{
		return self::$dice->create(Model\User\Cookie::class);
	}

	/**
	 * @return Model\Storage\IStorage
	 */
	public static function storage()
	{
		return self::$dice->create(Model\Storage\IStorage::class);
	}

	//
	// "Network" namespace
	//

	/**
	 * @return Network\IHTTPRequest
	 */
	public static function httpRequest()
	{
		return self::$dice->create(Network\IHTTPRequest::class);
	}

	//
	// "Repository" namespace
	//

	/**
	 * @return \Friendica\Domain\Repository\FSuggest;
	 */
	public static function fsuggest()
	{
		return self::$dice->create(Domain\Repository\FSuggest::class);
	}

	/**
	 * @return \Friendica\Domain\Repository\Introduction
	 */
	public static function intro()
	{
		return self::$dice->create(Domain\Repository\Introduction::class);
	}

	/**
	 * @return \Friendica\Domain\Repository\PermissionSet
	 */
	public static function permissionSet()
	{
		return self::$dice->create(Domain\Repository\PermissionSet::class);
	}

	/**
	 * @return \Friendica\Domain\Repository\ProfileField
	 */
	public static function profileField()
	{
		return self::$dice->create(Domain\Repository\ProfileField::class);
	}

	/**
	 * @return \Friendica\Domain\Repository\Notify
	 */
	public static function notify()
	{
		return self::$dice->create(Domain\Repository\Notify::class);
	}

	//
	// "Protocol" namespace instances
	//

	/**
	 * @return Protocol\Activity
	 */
	public static function activity()
	{
		return self::$dice->create(Protocol\Activity::class);
	}

	//
	// "Util" namespace instances
	//

	/**
	 * @return Util\ACLFormatter
	 */
	public static function aclFormatter()
	{
		return self::$dice->create(Util\ACLFormatter::class);
	}

	/**
	 * @return string
	 */
	public static function basePath()
	{
		return self::$dice->create('$basepath');
	}

	/**
	 * @return Util\DateTimeFormat
	 */
	public static function dtFormat()
	{
		return self::$dice->create(Util\DateTimeFormat::class);
	}

	/**
	 * @return Util\FileSystem
	 */
	public static function fs()
	{
		return self::$dice->create(Util\FileSystem::class);
	}

	/**
	 * @return Util\Profiler
	 */
	public static function profiler()
	{
		return self::$dice->create(Util\Profiler::class);
	}

	/**
	 * @return Util\Emailer
	 */
	public static function emailer()
	{
		return self::$dice->create(Util\Emailer::class);
	}
}
