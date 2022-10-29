<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Library\Navigation\SystemMessages;
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

	/**
	 * Returns a clone of the current dice instance
	 * This usefull for overloading the current instance with mocked methods during tests
	 *
	 * @return Dice
	 */
	public static function getDice()
	{
		return clone self::$dice;
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
	public static function dba(): Database\Database
	{
		return self::$dice->create(Database\Database::class);
	}

	/**
	 * @return \Friendica\Database\Definition\DbaDefinition
	 */
	public static function dbaDefinition(): Database\Definition\DbaDefinition
	{
		return self::$dice->create(Database\Definition\DbaDefinition::class);
	}

	/**
	 * @return \Friendica\Database\Definition\ViewDefinition
	 */
	public static function viewDefinition(): Database\Definition\ViewDefinition
	{
		return self::$dice->create(Database\Definition\ViewDefinition::class);
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
	 * @return Content\Conversation
	 */
	public static function conversation()
	{
		return self::$dice->create(Content\Conversation::class);
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
	 * @return Core\Cache\Capability\ICanCache
	 */
	public static function cache()
	{
		return self::$dice->create(Core\Cache\Capability\ICanCache::class);
	}

	/**
	 * @return Core\Config\Capability\IManageConfigValues
	 */
	public static function config()
	{
		return self::$dice->create(Core\Config\Capability\IManageConfigValues::class);
	}

	/**
	 * @return Core\PConfig\Capability\IManagePersonalConfigValues
	 */
	public static function pConfig()
	{
		return self::$dice->create(Core\PConfig\Capability\IManagePersonalConfigValues::class);
	}

	/**
	 * @return Core\Lock\Capability\ICanLock
	 */
	public static function lock()
	{
		return self::$dice->create(Core\Lock\Capability\ICanLock::class);
	}

	/**
	 * @return Core\L10n
	 */
	public static function l10n()
	{
		return self::$dice->create(Core\L10n::class);
	}

	/**
	 * @return Core\Worker\Repository\Process
	 */
	public static function process()
	{
		return self::$dice->create(Core\Worker\Repository\Process::class);
	}

	public static function session(): IHandleSessions
	{
		return self::$dice->create(Core\Session\Capability\IHandleSessions::class);
	}

	public static function userSession(): IHandleUserSessions
	{
		return self::$dice->create(Core\Session\Capability\IHandleUserSessions::class);
	}

	/**
	 * @return \Friendica\Core\Storage\Repository\StorageManager
	 */
	public static function storageManager()
	{
		return self::$dice->create(Core\Storage\Repository\StorageManager::class);
	}

	/**
	 * @return \Friendica\Core\System
	 */
	public static function system()
	{
		return self::$dice->create(Core\System::class);
	}

	/**
	 * @return \Friendica\Library\Navigation\SystemMessages
	 */
	public static function sysmsg()
	{
		return self::$dice->create(SystemMessages::class);
	}

	//
	// "LoggerInterface" instances
	//

	/**
	 * Flushes the Logger instance, so the factory is called again
	 * (creates a new id and retrieves the current PID)
	 */
	public static function flushLogger()
	{
		$flushDice = self::$dice
			->addRule(LoggerInterface::class, self::$dice->getRule(LoggerInterface::class))
			->addRule('$devLogger', self::$dice->getRule('$devLogger'));
		static::init($flushDice);
	}

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
		return self::$dice->create(Core\Logger\Type\WorkerLogger::class);
	}

	//
	// "Factory" namespace instances
	//

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Account
	 */
	public static function mstdnAccount()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Account::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Application
	 */
	public static function mstdnApplication()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Application::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Attachment
	 */
	public static function mstdnAttachment()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Attachment::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Card
	 */
	public static function mstdnCard()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Card::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Conversation
	 */
	public static function mstdnConversation()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Conversation::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Emoji
	 */
	public static function mstdnEmoji()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Emoji::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Error
	 */
	public static function mstdnError()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Error::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\FollowRequest
	 */
	public static function mstdnFollowRequest()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\FollowRequest::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Poll
	 */
	public static function mstdnPoll()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Poll::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Relationship
	 */
	public static function mstdnRelationship()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Relationship::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Status
	 */
	public static function mstdnStatus()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Status::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\ScheduledStatus
	 */
	public static function mstdnScheduledStatus()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\ScheduledStatus::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Subscription
	 */
	public static function mstdnSubscription()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Subscription::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\ListEntity
	 */
	public static function mstdnList()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\ListEntity::class);
	}

	/**
	 * @return \Friendica\Library\Api\Mastodon\Factory\Notification
	 */
	public static function mstdnNotification()
	{
		return self::$dice->create(Library\Api\Mastodon\Factory\Notification::class);
	}

	/**
	 * @return \Friendica\Library\Api\Twitter\Factory\Status
	 */
	public static function twitterStatus()
	{
		return self::$dice->create(Library\Api\Twitter\Factory\Status::class);
	}

	/**
	 * @return \Friendica\Library\Api\Twitter\Factory\User
	 */
	public static function twitterUser()
	{
		return self::$dice->create(Library\Api\Twitter\Factory\User::class);
	}

	public static function notificationIntro(): Library\Navigation\Notifications\Factory\Introduction
	{
		return self::$dice->create(Library\Navigation\Notifications\Factory\Introduction::class);
	}

	//
	// "Model" namespace instances
	//
	/**
	 * @return \Friendica\Core\Worker\Repository\Process
	 */
	public static function modelProcess()
	{
		return self::$dice->create(Core\Worker\Repository\Process::class);
	}

	/**
	 * @return Model\User\Cookie
	 */
	public static function cookie()
	{
		return self::$dice->create(Model\User\Cookie::class);
	}

	/**
	 * @return Core\Storage\Capability\ICanWriteToStorage
	 */
	public static function storage()
	{
		return self::$dice->create(Core\Storage\Capability\ICanWriteToStorage::class);
	}

	/**
	 * @return Model\Log\ParsedLogIterator
	 */
	public static function parsedLogIterator()
	{
		return self::$dice->create(Model\Log\ParsedLogIterator::class);
	}

	//
	// "Module" namespace
	//

	public static function apiResponse(): Module\Api\ApiResponse
	{
		return self::$dice->create(Module\Api\ApiResponse::class);
	}

	//
	// "Network" namespace
	//

	/**
	 * @return \Friendica\Library\Network\HTTPClient\Capability\ICanSendHttpRequests
	 */
	public static function httpClient()
	{
		return self::$dice->create(Library\Network\HTTPClient\Capability\ICanSendHttpRequests::class);
	}

	//
	// "Repository" namespace
	//

	/**
	 * @return \Friendica\Library\Contact\FriendSuggest\Repository\FriendSuggest;
	 */
	public static function fsuggest()
	{
		return self::$dice->create(Library\Contact\FriendSuggest\Repository\FriendSuggest::class);
	}

	/**
	 * @return \Friendica\Library\Contact\FriendSuggest\Factory\FriendSuggest;
	 */
	public static function fsuggestFactory()
	{
		return self::$dice->create(Library\Contact\FriendSuggest\Factory\FriendSuggest::class);
	}

	/**
	 * @return \Friendica\Library\Contact\Introduction\Repository\Introduction
	 */
	public static function intro()
	{
		return self::$dice->create(Library\Contact\Introduction\Repository\Introduction::class);
	}

	/**
	 * @return \Friendica\Library\Contact\Introduction\Factory\Introduction
	 */
	public static function introFactory()
	{
		return self::$dice->create(Library\Contact\Introduction\Factory\Introduction::class);
	}

	public static function localRelationship(): Library\Contact\LocalRelationship\Repository\LocalRelationship
	{
		return self::$dice->create(Library\Contact\LocalRelationship\Repository\LocalRelationship::class);
	}

	public static function permissionSet(): Library\Security\PermissionSet\Repository\PermissionSet
	{
		return self::$dice->create(Library\Security\PermissionSet\Repository\PermissionSet::class);
	}

	public static function permissionSetFactory(): Library\Security\PermissionSet\Factory\PermissionSet
	{
		return self::$dice->create(Library\Security\PermissionSet\Factory\PermissionSet::class);
	}

	public static function profileField(): Library\Profile\ProfileField\Repository\ProfileField
	{
		return self::$dice->create(Library\Profile\ProfileField\Repository\ProfileField::class);
	}

	public static function profileFieldFactory(): Library\Profile\ProfileField\Factory\ProfileField
	{
		return self::$dice->create(Library\Profile\ProfileField\Factory\ProfileField::class);
	}

	public static function notification(): Library\Navigation\Notifications\Repository\Notification
	{
		return self::$dice->create(Library\Navigation\Notifications\Repository\Notification::class);
	}

	public static function notificationFactory(): Library\Navigation\Notifications\Factory\Notification
	{
		return self::$dice->create(Library\Navigation\Notifications\Factory\Notification::class);
	}

	public static function notify(): Library\Navigation\Notifications\Repository\Notify
	{
		return self::$dice->create(Library\Navigation\Notifications\Repository\Notify::class);
	}

	public static function notifyFactory(): Library\Navigation\Notifications\Factory\Notify
	{
		return self::$dice->create(Library\Navigation\Notifications\Factory\Notify::class);
	}

	public static function formattedNotificationFactory(): Library\Navigation\Notifications\Factory\FormattedNotify
	{
		return self::$dice->create(Library\Navigation\Notifications\Factory\FormattedNotify::class);
	}

	public static function formattedNavNotificationFactory(): Library\Navigation\Notifications\Factory\FormattedNavNotification
	{
		return self::$dice->create(Library\Navigation\Notifications\Factory\FormattedNavNotification::class);
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
