<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
	 * @return Factory\Api\Mastodon\Account
	 */
	public static function mstdnAccount()
	{
		return self::$dice->create(Factory\Api\Mastodon\Account::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Application
	 */
	public static function mstdnApplication()
	{
		return self::$dice->create(Factory\Api\Mastodon\Application::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Attachment
	 */
	public static function mstdnAttachment()
	{
		return self::$dice->create(Factory\Api\Mastodon\Attachment::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Card
	 */
	public static function mstdnCard()
	{
		return self::$dice->create(Factory\Api\Mastodon\Card::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Conversation
	 */
	public static function mstdnConversation()
	{
		return self::$dice->create(Factory\Api\Mastodon\Conversation::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Emoji
	 */
	public static function mstdnEmoji()
	{
		return self::$dice->create(Factory\Api\Mastodon\Emoji::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Error
	 */
	public static function mstdnError()
	{
		return self::$dice->create(Factory\Api\Mastodon\Error::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Field
	 */
	public static function mstdnField()
	{
		return self::$dice->create(Factory\Api\Mastodon\Field::class);
	}

	/**
	 * @return Factory\Api\Mastodon\FollowRequest
	 */
	public static function mstdnFollowRequest()
	{
		return self::$dice->create(Factory\Api\Mastodon\FollowRequest::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Relationship
	 */
	public static function mstdnRelationship()
	{
		return self::$dice->create(Factory\Api\Mastodon\Relationship::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Status
	 */
	public static function mstdnStatus()
	{
		return self::$dice->create(Factory\Api\Mastodon\Status::class);
	}

	/**
	 * @return Factory\Api\Mastodon\ListEntity
	 */
	public static function mstdnList()
	{
		return self::$dice->create(Factory\Api\Mastodon\ListEntity::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Mention
	 */
	public static function mstdnMention()
	{
		return self::$dice->create(Factory\Api\Mastodon\Mention::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Notification
	 */
	public static function mstdnNotification()
	{
		return self::$dice->create(Factory\Api\Mastodon\Notification::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Tag
	 */
	public static function mstdnTag()
	{
		return self::$dice->create(Factory\Api\Mastodon\Tag::class);
	}

	/**
	 * @return Factory\Api\Twitter\User
	 */
	public static function twitterUser()
	{
		return self::$dice->create(Factory\Api\Twitter\User::class);
	}

	/**
	 * @return Factory\Notification\Notification
	 */
	public static function notification()
	{
		return self::$dice->create(Factory\Notification\Notification::class);
	}

	/**
	 * @return Factory\Notification\Introduction
	 */
	public static function notificationIntro()
	{
		return self::$dice->create(Factory\Notification\Introduction::class);
	}

	//
	// "Model" namespace instances
	//
	/**
	 * @return Model\Process
	 */
	public static function modelProcess()
	{
		return self::$dice->create(Model\Process::class);
	}

	/**
	 * @return Model\User\Cookie
	 */
	public static function cookie()
	{
		return self::$dice->create(Model\User\Cookie::class);
	}

	/**
	 * @return Model\Host
	 */
	public static function host()
	{
		return self::$dice->create(Model\Host::class);
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
	 * @return Repository\FSuggest;
	 */
	public static function fsuggest()
	{
		return self::$dice->create(Repository\FSuggest::class);
	}

	/**
	 * @return Repository\Introduction
	 */
	public static function intro()
	{
		return self::$dice->create(Repository\Introduction::class);
	}

	/**
	 * @return Repository\PermissionSet
	 */
	public static function permissionSet()
	{
		return self::$dice->create(Repository\PermissionSet::class);
	}

	/**
	 * @return Repository\ProfileField
	 */
	public static function profileField()
	{
		return self::$dice->create(Repository\ProfileField::class);
	}

	/**
	 * @return Repository\Notification
	 */
	public static function notify()
	{
		return self::$dice->create(Repository\Notification::class);
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
	// "Security" namespace instances
	//

	/**
	 * @return \Friendica\Security\Authentication
	 */
	public static function auth()
	{
		return self::$dice->create(Security\Authentication::class);
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
