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
	 */
	public static function getDice(): Dice
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

	public static function dba(): Database\Database
	{
		return self::$dice->create(Database\Database::class);
	}

	public static function dbaDefinition(): Database\Definition\DbaDefinition
	{
		return self::$dice->create(Database\Definition\DbaDefinition::class);
	}

	public static function viewDefinition(): Database\Definition\ViewDefinition
	{
		return self::$dice->create(Database\Definition\ViewDefinition::class);
	}

	//
	// "App" namespace instances
	//

	public static function args(): App\Arguments
	{
		return self::$dice->create(App\Arguments::class);
	}

	public static function baseUrl(): App\BaseURL
	{
		return self::$dice->create(App\BaseURL::class);
	}

	public static function mode(): App\Mode
	{
		return self::$dice->create(App\Mode::class);
	}

	public static function page(): App\Page
	{
		return self::$dice->create(App\Page::class);
	}

	public static function router(): App\Router
	{
		return self::$dice->create(App\Router::class);
	}

	//
	// "AtProtocol" namespace instances
	//

	public static function atProtocol(): ATProtocol
	{
		return self::$dice->create(ATProtocol::class);
	}

	public static function atpActor(): ATProtocol\Actor
	{
		return self::$dice->create(ATProtocol\Actor::class);
	}

	public static function atpProcessor(): ATProtocol\Processor
	{
		return self::$dice->create(ATProtocol\Processor::class);
	}

	//
	// "Content" namespace instances
	//

	public static function contentItem(): Content\Item
	{
		return self::$dice->create(Content\Item::class);
	}

	public static function conversation(): Content\Conversation
	{
		return self::$dice->create(Content\Conversation::class);
	}

	public static function bbCodeVideo(): Content\Text\BBCode\Video
	{
		return self::$dice->create(Content\Text\BBCode\Video::class);
	}

	//
	// "Core" namespace instances
	//

	public static function cache(): Core\Cache\Capability\ICanCache
	{
		return self::$dice->create(Core\Cache\Capability\ICanCache::class);
	}

	public static function config(): Core\Config\Capability\IManageConfigValues
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

	public static function pConfig(): Core\PConfig\Capability\IManagePersonalConfigValues
	{
		return self::$dice->create(Core\PConfig\Capability\IManagePersonalConfigValues::class);
	}

	public static function lock(): Core\Lock\Capability\ICanLock
	{
		return self::$dice->create(Core\Lock\Capability\ICanLock::class);
	}

	public static function l10n(): Core\L10n
	{
		return self::$dice->create(Core\L10n::class);
	}

	public static function process(): Core\Worker\Repository\Process
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

	public static function storageManager(): Core\Storage\Repository\StorageManager
	{
		return self::$dice->create(Core\Storage\Repository\StorageManager::class);
	}

	public static function addonHelper(): AddonHelper
	{
		return self::$dice->create(AddonHelper::class);
	}

	public static function system(): Core\System
	{
		return self::$dice->create(Core\System::class);
	}

	public static function sysmsg(): SystemMessages
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

	public static function logger(): LoggerInterface
	{
		return self::$dice->create(LoggerInterface::class);
	}

	/**
	 * @deprecated 2025.07 Use `DI::loggerManager()` and `DI::logger()` instead
	 */
	public static function workerLogger(): Core\Logger\Type\WorkerLogger
	{
		@trigger_error('`' . __METHOD__ . '()` is deprecated since 2025.07 and will be removed after 5 months, use `DI::logger()` instead.', E_USER_DEPRECATED);

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

	public static function mstdnAccount(): Factory\Api\Mastodon\Account
	{
		return self::$dice->create(Factory\Api\Mastodon\Account::class);
	}

	public static function mstdnApplication(): Factory\Api\Mastodon\Application
	{
		return self::$dice->create(Factory\Api\Mastodon\Application::class);
	}

	public static function mstdnAttachment(): Factory\Api\Mastodon\Attachment
	{
		return self::$dice->create(Factory\Api\Mastodon\Attachment::class);
	}

	public static function mstdnCard(): Factory\Api\Mastodon\Card
	{
		return self::$dice->create(Factory\Api\Mastodon\Card::class);
	}

	public static function mstdnConversation(): Factory\Api\Mastodon\Conversation
	{
		return self::$dice->create(Factory\Api\Mastodon\Conversation::class);
	}

	public static function mstdnEmoji(): Factory\Api\Mastodon\Emoji
	{
		return self::$dice->create(Factory\Api\Mastodon\Emoji::class);
	}

	public static function mstdnError(): Factory\Api\Mastodon\Error
	{
		return self::$dice->create(Factory\Api\Mastodon\Error::class);
	}

	public static function mstdnPoll(): Factory\Api\Mastodon\Poll
	{
		return self::$dice->create(Factory\Api\Mastodon\Poll::class);
	}

	public static function mstdnRelationship(): Factory\Api\Mastodon\Relationship
	{
		return self::$dice->create(Factory\Api\Mastodon\Relationship::class);
	}

	public static function mstdnStatus(): Factory\Api\Mastodon\Status
	{
		return self::$dice->create(Factory\Api\Mastodon\Status::class);
	}

	public static function mstdnStatusSource(): Factory\Api\Mastodon\StatusSource
	{
		return self::$dice->create(Factory\Api\Mastodon\StatusSource::class);
	}

	public static function mstdnScheduledStatus(): Factory\Api\Mastodon\ScheduledStatus
	{
		return self::$dice->create(Factory\Api\Mastodon\ScheduledStatus::class);
	}

	public static function mstdnSubscription(): Factory\Api\Mastodon\Subscription
	{
		return self::$dice->create(Factory\Api\Mastodon\Subscription::class);
	}

	public static function mstdnList(): Factory\Api\Mastodon\ListEntity
	{
		return self::$dice->create(Factory\Api\Mastodon\ListEntity::class);
	}

	public static function mstdnNotification(): Factory\Api\Mastodon\Notification
	{
		return self::$dice->create(Factory\Api\Mastodon\Notification::class);
	}

	public static function twitterStatus(): Factory\Api\Twitter\Status
	{
		return self::$dice->create(Factory\Api\Twitter\Status::class);
	}

	public static function twitterUser(): Factory\Api\Twitter\User
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
	public static function modelProcess(): Core\Worker\Repository\Process
	{
		return self::$dice->create(Core\Worker\Repository\Process::class);
	}

	public static function cookie(): Model\User\Cookie
	{
		return self::$dice->create(Model\User\Cookie::class);
	}

	public static function storage(): Core\Storage\Capability\ICanWriteToStorage
	{
		return self::$dice->create(Core\Storage\Capability\ICanWriteToStorage::class);
	}

	public static function parsedLogIterator(): Model\Log\ParsedLogIterator
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

	public static function httpClient(): Network\HTTPClient\Capability\ICanSendHttpRequests
	{
		return self::$dice->create(Network\HTTPClient\Capability\ICanSendHttpRequests::class);
	}

	public static function robotsTxt(): Network\RobotsTxt
	{
		return self::$dice->create(Network\RobotsTxt::class);
	}

	//
	// "Repository" namespace
	//

	public static function fsuggest(): Contact\FriendSuggest\Repository\FriendSuggest
	{
		return self::$dice->create(Contact\FriendSuggest\Repository\FriendSuggest::class);
	}

	public static function fsuggestFactory(): Contact\FriendSuggest\Factory\FriendSuggest
	{
		return self::$dice->create(Contact\FriendSuggest\Factory\FriendSuggest::class);
	}

	public static function TimelineFactory(): Content\Conversation\Factory\Timeline
	{
		return self::$dice->create(Content\Conversation\Factory\Timeline::class);
	}

	public static function CommunityFactory(): Content\Conversation\Factory\Community
	{
		return self::$dice->create(Content\Conversation\Factory\Community::class);
	}

	public static function ChannelFactory(): Content\Conversation\Factory\Channel
	{
		return self::$dice->create(Content\Conversation\Factory\Channel::class);
	}

	public static function ChannelPost(): Content\Conversation\Factory\ChannelPost
	{
		return self::$dice->create(Content\Conversation\Factory\ChannelPost::class);
	}

	public static function SystemChannelPost(): Content\Conversation\Factory\SystemChannelPost
	{
		return self::$dice->create(Content\Conversation\Factory\SystemChannelPost::class);
	}

	public static function userDefinedChannel(): Content\Conversation\Repository\UserDefinedChannel
	{
		return self::$dice->create(Content\Conversation\Repository\UserDefinedChannel::class);
	}

	public static function NetworkFactory(): Content\Conversation\Factory\Network
	{
		return self::$dice->create(Content\Conversation\Factory\Network::class);
	}

	public static function intro(): Contact\Introduction\Repository\Introduction
	{
		return self::$dice->create(Contact\Introduction\Repository\Introduction::class);
	}

	public static function introFactory(): Contact\Introduction\Factory\Introduction
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

	public static function activity(): Protocol\Activity
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

	public static function auth(): Security\Authentication
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

	public static function aclFormatter(): Util\ACLFormatter
	{
		return self::$dice->create(Util\ACLFormatter::class);
	}

	public static function basePath(): string
	{
		/** @var BasePath */
		$basePath = self::$dice->create(BasePath::class);

		return $basePath->getPath();
	}

	public static function dtFormat(): Util\DateTimeFormat
	{
		return self::$dice->create(Util\DateTimeFormat::class);
	}

	public static function profiler(): Util\Profiler
	{
		return self::$dice->create(Util\Profiler::class);
	}

	public static function emailer(): Util\Emailer
	{
		return self::$dice->create(Util\Emailer::class);
	}

	public static function postMediaRepository(): Content\Post\Repository\PostMedia
	{
		return self::$dice->create(Content\Post\Repository\PostMedia::class);
	}

	public static function postMediaFactory(): Content\Post\Factory\PostMedia
	{
		return self::$dice->create(Content\Post\Factory\PostMedia::class);
	}

	/**
	 * @internal The EventDispatcher should never called outside of the core, like in addons or themes
	 * @deprecated 2025.07 Use constructor injection instead
	 */
	public static function eventDispatcher(): \Psr\EventDispatcher\EventDispatcherInterface
	{
		return self::$dice->create(\Psr\EventDispatcher\EventDispatcherInterface::class);
	}
}
