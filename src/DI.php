<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Dice\Dice;
use Friendica\Core\Addon\AddonHelper;
use Friendica\Core\Logger\Capability\ICheckLoggerSettings;
use Friendica\Core\Logger\LoggerManager;
use Friendica\Core\Logger\Util\LoggerSettingsCheck;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Navigation\SystemMessages;
use Friendica\Protocol\ATProtocol;
use Friendica\Util\BasePath;
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

	/**
	 * Initialize the singleton DI container with the Dice instance
	 *
	 * @param Dice $dice             The Dice instance
	 * @param bool $disableDepByHand If true, the database dependencies aren't set, thus any occurrence of logging or
	 *                               profiling in database methods would lead to an error. This flag is for testing only.
	 *
	 * @return void
	 */
	public static function init(Dice $dice, bool $disableDepByHand = false)
	{
		self::$dice = $dice;

		if (!$disableDepByHand) {
			self::setCompositeRootDependencyByHand();
		}
	}

	/**
	 * I HATE this method, but everything else needs refactoring at the database itself
	 * Set the database dependencies manually, because of current, circular dependencies between the database and the config table
	 *
	 * @todo Instead of this madness, split the database in a core driver-dependent (mysql, mariadb, postgresql, ..) part without any other dependency unlike credentials and in the full-featured, driver-independent database class with all dependencies
	 */
	public static function setCompositeRootDependencyByHand()
	{
		$database = static::dba();
		$database->setDependency(static::config(), static::profiler(), static::logger(), static::lock());
	}

	/**
	 * Returns a clone of the current dice instance
	 *
	 * @internal This useful for overloading the current instance with mocked methods during tests
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

	public static function appHelper(): AppHelper
	{
		return self::$dice->create(AppHelper::class);
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

	public static function baseUrl(): App\BaseURL
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
	// "AtProtocol" namespace instances
	//

	/**
	 * @return AtProtocol
	 */
	public static function atProtocol()
	{
		return self::$dice->create(ATProtocol::class);
	}

	/**
	 * @return ATProtocol\Actor
	 */
	public static function atpActor()
	{
		return self::$dice->create(ATProtocol\Actor::class);
	}

	/**
	 * @return AtProtocol\Processor
	 */
	public static function atpProcessor()
	{
		return self::$dice->create(ATProtocol\Processor::class);
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

	public static function configFileManager(): Core\Config\Util\ConfigFileManager
	{
		return self::$dice->create(Core\Config\Util\ConfigFileManager::class);
	}

	public static function keyValue(): Core\KeyValueStorage\Capability\IManageKeyValuePairs
	{
		return self::$dice->create(Core\KeyValueStorage\Capability\IManageKeyValuePairs::class);
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

	public static function addonHelper(): AddonHelper
	{
		return self::$dice->create(AddonHelper::class);
	}

	/**
	 * @return \Friendica\Core\System
	 */
	public static function system()
	{
		return self::$dice->create(Core\System::class);
	}

	/**
	 * @return \Friendica\Navigation\SystemMessages
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
			->addRule(LoggerInterface::class, self::$dice->getRule(LoggerInterface::class));

		static::init($flushDice);
	}

	public static function logCheck(): ICheckLoggerSettings
	{
		return self::$dice->create(LoggerSettingsCheck::class);
	}

	/**
	 * @return LoggerInterface
	 */
	public static function logger()
	{
		return self::$dice->create(LoggerInterface::class);
	}

	/**
	 * @deprecated 2025.02 Use `DI::loggerManager()` and `DI::logger()` instead
	 *
	 * @return \Friendica\Core\Logger\Type\WorkerLogger
	 */
	public static function workerLogger()
	{
		@trigger_error('`' . __METHOD__ . '()` is deprecated since 2025.02 and will be removed after 5 months, use `DI::logger()` instead.', E_USER_DEPRECATED);

		return self::$dice->create(Core\Logger\Type\WorkerLogger::class);
	}

	/**
	 * @internal Only for use in Friendica\Core\Worker class
	 *
	 * @see Friendica\Core\Worker::execFunction()
	 */
	public static function loggerManager(): LoggerManager
	{
		return self::$dice->create(LoggerManager::class);
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
	 * @return Factory\Api\Mastodon\Poll
	 */
	public static function mstdnPoll()
	{
		return self::$dice->create(Factory\Api\Mastodon\Poll::class);
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
	 * @return Factory\Api\Mastodon\StatusSource
	 */
	public static function mstdnStatusSource()
	{
		return self::$dice->create(Factory\Api\Mastodon\StatusSource::class);
	}

	/**
	 * @return Factory\Api\Mastodon\ScheduledStatus
	 */
	public static function mstdnScheduledStatus()
	{
		return self::$dice->create(Factory\Api\Mastodon\ScheduledStatus::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Subscription
	 */
	public static function mstdnSubscription()
	{
		return self::$dice->create(Factory\Api\Mastodon\Subscription::class);
	}

	/**
	 * @return Factory\Api\Mastodon\ListEntity
	 */
	public static function mstdnList()
	{
		return self::$dice->create(Factory\Api\Mastodon\ListEntity::class);
	}

	/**
	 * @return Factory\Api\Mastodon\Notification
	 */
	public static function mstdnNotification()
	{
		return self::$dice->create(Factory\Api\Mastodon\Notification::class);
	}

	/**
	 * @return Factory\Api\Twitter\Status
	 */
	public static function twitterStatus()
	{
		return self::$dice->create(Factory\Api\Twitter\Status::class);
	}

	/**
	 * @return Factory\Api\Twitter\User
	 */
	public static function twitterUser()
	{
		return self::$dice->create(Factory\Api\Twitter\User::class);
	}

	public static function notificationIntro(): Navigation\Notifications\Factory\Introduction
	{
		return self::$dice->create(Navigation\Notifications\Factory\Introduction::class);
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
	 * @return Network\HTTPClient\Capability\ICanSendHttpRequests
	 */
	public static function httpClient()
	{
		return self::$dice->create(Network\HTTPClient\Capability\ICanSendHttpRequests::class);
	}

	//
	// "Repository" namespace
	//

	/**
	 * @return Contact\FriendSuggest\Repository\FriendSuggest
	 */
	public static function fsuggest()
	{
		return self::$dice->create(Contact\FriendSuggest\Repository\FriendSuggest::class);
	}

	/**
	 * @return Contact\FriendSuggest\Factory\FriendSuggest
	 */
	public static function fsuggestFactory()
	{
		return self::$dice->create(Contact\FriendSuggest\Factory\FriendSuggest::class);
	}

	/**
	 * @return Content\Conversation\Factory\Timeline
	 */
	public static function TimelineFactory()
	{
		return self::$dice->create(Content\Conversation\Factory\Timeline::class);
	}

	/**
	 * @return Content\Conversation\Factory\Community
	 */
	public static function CommunityFactory()
	{
		return self::$dice->create(Content\Conversation\Factory\Community::class);
	}

	/**
	 * @return Content\Conversation\Factory\Channel
	 */
	public static function ChannelFactory()
	{
		return self::$dice->create(Content\Conversation\Factory\Channel::class);
	}

	public static function userDefinedChannel(): Content\Conversation\Repository\UserDefinedChannel
	{
		return self::$dice->create(Content\Conversation\Repository\UserDefinedChannel::class);
	}

	/**
	 * @return Content\Conversation\Factory\Network
	 */
	public static function NetworkFactory()
	{
		return self::$dice->create(Content\Conversation\Factory\Network::class);
	}

	/**
	 * @return Contact\Introduction\Repository\Introduction
	 */
	public static function intro()
	{
		return self::$dice->create(Contact\Introduction\Repository\Introduction::class);
	}

	/**
	 * @return Contact\Introduction\Factory\Introduction
	 */
	public static function introFactory()
	{
		return self::$dice->create(Contact\Introduction\Factory\Introduction::class);
	}

	public static function report(): Moderation\Repository\Report
	{
		return self::$dice->create(Moderation\Repository\Report::class);
	}

	public static function reportFactory(): Moderation\Factory\Report
	{
		return self::$dice->create(Moderation\Factory\Report::class);
	}

	public static function localRelationship(): Contact\LocalRelationship\Repository\LocalRelationship
	{
		return self::$dice->create(Contact\LocalRelationship\Repository\LocalRelationship::class);
	}

	public static function permissionSet(): Security\PermissionSet\Repository\PermissionSet
	{
		return self::$dice->create(Security\PermissionSet\Repository\PermissionSet::class);
	}

	public static function permissionSetFactory(): Security\PermissionSet\Factory\PermissionSet
	{
		return self::$dice->create(Security\PermissionSet\Factory\PermissionSet::class);
	}

	public static function profileField(): Profile\ProfileField\Repository\ProfileField
	{
		return self::$dice->create(Profile\ProfileField\Repository\ProfileField::class);
	}

	public static function profileFieldFactory(): Profile\ProfileField\Factory\ProfileField
	{
		return self::$dice->create(Profile\ProfileField\Factory\ProfileField::class);
	}

	public static function notification(): Navigation\Notifications\Repository\Notification
	{
		return self::$dice->create(Navigation\Notifications\Repository\Notification::class);
	}

	public static function notificationFactory(): Navigation\Notifications\Factory\Notification
	{
		return self::$dice->create(Navigation\Notifications\Factory\Notification::class);
	}

	public static function notify(): Navigation\Notifications\Repository\Notify
	{
		return self::$dice->create(Navigation\Notifications\Repository\Notify::class);
	}

	public static function notifyFactory(): Navigation\Notifications\Factory\Notify
	{
		return self::$dice->create(Navigation\Notifications\Factory\Notify::class);
	}

	public static function formattedNotificationFactory(): Navigation\Notifications\Factory\FormattedNotify
	{
		return self::$dice->create(Navigation\Notifications\Factory\FormattedNotify::class);
	}

	public static function formattedNavNotificationFactory(): Navigation\Notifications\Factory\FormattedNavNotification
	{
		return self::$dice->create(Navigation\Notifications\Factory\FormattedNavNotification::class);
	}

	//
	// "Federation" namespace instances
	//

	public static function deliveryQueueItemFactory(): Federation\Factory\DeliveryQueueItem
	{
		return self::$dice->create(Federation\Factory\DeliveryQueueItem::class);
	}

	public static function deliveryQueueItemRepo(): Federation\Repository\DeliveryQueueItem
	{
		return self::$dice->create(Federation\Repository\DeliveryQueueItem::class);
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

	public static function dsprContact(): Protocol\Diaspora\Repository\DiasporaContact
	{
		return self::$dice->create(Protocol\Diaspora\Repository\DiasporaContact::class);
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
	// "User" namespace instances
	//

	public static function userGServer(): User\Settings\Repository\UserGServer
	{
		return self::$dice->create(User\Settings\Repository\UserGServer::class);
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
		/** @var BasePath */
		$basePath = self::$dice->create(BasePath::class);

		return $basePath->getPath();
	}

	/**
	 * @return Util\DateTimeFormat
	 */
	public static function dtFormat()
	{
		return self::$dice->create(Util\DateTimeFormat::class);
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

	public static function postMediaRepository(): Content\Post\Repository\PostMedia
	{
		return self::$dice->create(Content\Post\Repository\PostMedia::class);
	}

	/**
	 * @internal The EventDispatcher should never called outside of the core, like in addons or themes
	 * @deprecated 2025.02 Use constructor injection instead
	 */
	public static function eventDispatcher(): \Psr\EventDispatcher\EventDispatcherInterface
	{
		return self::$dice->create(\Psr\EventDispatcher\EventDispatcherInterface::class);
	}
}
