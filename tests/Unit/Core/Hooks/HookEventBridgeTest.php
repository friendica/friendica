<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Hooks;

use FastRoute\RouteCollector;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Hooks\HookEventBridge;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\CollectRoutesEvent;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\Event;
use Friendica\Event\HtmlFilterEvent;
use PHPUnit\Framework\TestCase;

class HookEventBridgeTest extends TestCase
{
	public function testGetStaticSubscribedEventsReturnsStaticMethods(): void
	{
		$expected = [
			Event::INIT                                       => 'onNamedEvent',
			Event::HOME_INIT                                  => 'onNamedEvent',
			ConfigLoadedEvent::CONFIG_LOADED                  => 'onConfigLoadedEvent',
			CollectRoutesEvent::COLLECT_ROUTES                => 'onCollectRoutesEvent',
			ArrayFilterEvent::APP_MENU                        => 'onArrayFilterEvent',
			ArrayFilterEvent::NAV_INFO                        => 'onArrayFilterEvent',
			ArrayFilterEvent::FEATURE_ENABLED                 => 'onArrayFilterEvent',
			ArrayFilterEvent::FEATURE_GET                     => 'onArrayFilterEvent',
			ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT      => 'onPermissionTooltipContentEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL_START         => 'onArrayFilterEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL               => 'onInsertPostLocalEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL_END           => 'onInsertPostLocalEndEvent',
			ArrayFilterEvent::INSERT_POST_REMOTE              => 'onArrayFilterEvent',
			ArrayFilterEvent::INSERT_POST_REMOTE_END          => 'onArrayFilterEvent',
			ArrayFilterEvent::PREPARE_POST_START              => 'onPreparePostStartEvent',
			ArrayFilterEvent::PREPARE_POST_FILTER_CONTENT     => 'onArrayFilterEvent',
			ArrayFilterEvent::PREPARE_POST                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PREPARE_POST_END                => 'onArrayFilterEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'onArrayFilterEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_START              => 'onPhotoUploadStartEvent',
			ArrayFilterEvent::PHOTO_UPLOAD                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_END                => 'onPhotoUploadEndEvent',
			ArrayFilterEvent::NETWORK_TO_NAME                 => 'onArrayFilterEvent',
			ArrayFilterEvent::NETWORK_CONTENT_START           => 'onArrayFilterEvent',
			ArrayFilterEvent::NETWORK_CONTENT_TABS            => 'onArrayFilterEvent',
			ArrayFilterEvent::PARSE_LINK                      => 'onArrayFilterEvent',
			ArrayFilterEvent::CONVERSATION_START              => 'onArrayFilterEvent',
			ArrayFilterEvent::FETCH_ITEM_BY_LINK              => 'onArrayFilterEvent',
			ArrayFilterEvent::ITEM_TAGGED                     => 'onArrayFilterEvent',
			ArrayFilterEvent::DISPLAY_ITEM                    => 'onArrayFilterEvent',
			ArrayFilterEvent::CACHE_ITEM                      => 'onArrayFilterEvent',
			ArrayFilterEvent::CHECK_ITEM_NOTIFICATION         => 'onArrayFilterEvent',
			ArrayFilterEvent::ENOTIFY                         => 'onArrayFilterEvent',
			ArrayFilterEvent::ENOTIFY_STORE                   => 'onArrayFilterEvent',
			ArrayFilterEvent::ENOTIFY_MAIL                    => 'onArrayFilterEvent',
			ArrayFilterEvent::DETECT_LANGUAGES                => 'onArrayFilterEvent',
			ArrayFilterEvent::RENDER_LOCATION                 => 'onArrayFilterEvent',
			ArrayFilterEvent::ITEM_PHOTO_MENU                 => 'onArrayFilterEvent',
			ArrayFilterEvent::DIRECTORY_ITEM                  => 'onArrayFilterEvent',
			ArrayFilterEvent::CONTACT_PHOTO_MENU              => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SIDEBAR_ENTRY           => 'onProfileSidebarEntryEvent',
			ArrayFilterEvent::PROFILE_SIDEBAR                 => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_TABS                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SETTINGS_FORM           => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SETTINGS_POST           => 'onArrayFilterEvent',
			ArrayFilterEvent::MODERATION_USERS_TABS           => 'onArrayFilterEvent',
			ArrayFilterEvent::ACL_LOOKUP_END                  => 'onArrayFilterEvent',
			ArrayFilterEvent::OEMBED_FETCH_END                => 'onOembedFetchEndEvent',
			ArrayFilterEvent::PAGE_INFO                       => 'onArrayFilterEvent',
			ArrayFilterEvent::SMILEY_LIST                     => 'onArrayFilterEvent',
			ArrayFilterEvent::BBCODE_TO_HTML_START            => 'onBbcodeToHtmlEvent',
			ArrayFilterEvent::HTML_TO_BBCODE_END              => 'onHtmlToBbcodeEvent',
			ArrayFilterEvent::BBCODE_TO_MARKDOWN_END          => 'onBbcodeToMarkdownEvent',
			ArrayFilterEvent::JOT_NETWORKS                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW        => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE         => 'onArrayFilterEvent',
			ArrayFilterEvent::FOLLOW_CONTACT                  => 'onArrayFilterEvent',
			ArrayFilterEvent::UNFOLLOW_CONTACT                => 'onArrayFilterEvent',
			ArrayFilterEvent::REVOKE_FOLLOW_CONTACT           => 'onArrayFilterEvent',
			ArrayFilterEvent::BLOCK_CONTACT                   => 'onArrayFilterEvent',
			ArrayFilterEvent::UNBLOCK_CONTACT                 => 'onArrayFilterEvent',
			ArrayFilterEvent::EDIT_CONTACT_FORM               => 'onArrayFilterEvent',
			ArrayFilterEvent::EDIT_CONTACT_POST               => 'onArrayFilterEvent',
			ArrayFilterEvent::AVATAR_LOOKUP                   => 'onArrayFilterEvent',
			ArrayFilterEvent::ACCOUNT_AUTHENTICATE            => 'onArrayFilterEvent',
			ArrayFilterEvent::ACCOUNT_REGISTER_FORM           => 'onArrayFilterEvent',
			ArrayFilterEvent::ACCOUNT_REGISTER_POST           => 'onArrayFilterEvent',
			ArrayFilterEvent::ACCOUNT_REGISTER                => 'onAccountRegisterEvent',
			ArrayFilterEvent::ACCOUNT_REMOVE                  => 'onAccountRemoveEvent',
			ArrayFilterEvent::EVENT_CREATED                   => 'onEventCreatedEvent',
			ArrayFilterEvent::EVENT_UPDATED                   => 'onEventUpdatedEvent',
			ArrayFilterEvent::ADD_WORKER_TASK                 => 'onArrayFilterEvent',
			ArrayFilterEvent::STORAGE_CONFIG                  => 'onArrayFilterEvent',
			ArrayFilterEvent::STORAGE_INSTANCE                => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_STRUCTURE_DEFINITION         => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_VIEW_DEFINITION              => 'onArrayFilterEvent',
			HtmlFilterEvent::HEAD                             => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER                           => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_HEADER                      => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP                 => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END                         => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_HOME_CONTENT                 => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_ABOUT_CONTENT                => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_PROFILE_CONTENT              => 'onHtmlFilterEvent',
			HtmlFilterEvent::JOT_TOOL                         => 'onHtmlFilterEvent',
			HtmlFilterEvent::CONTACT_BLOCK_END                => 'onHtmlFilterEvent',
		];

		$this->assertSame(
			$expected,
			HookEventBridge::getStaticSubscribedEvents()
		);

		foreach ($expected as $methodName) {
			$this->assertTrue(
				method_exists(HookEventBridge::class, $methodName),
				$methodName . '() is not defined'
			);

			$this->assertTrue(
				(new \ReflectionMethod(HookEventBridge::class, $methodName))->isStatic(),
				$methodName . '() is not static'
			);
		}
	}

	public static function getNamedEventData(): array
	{
		return [
			['test', 'test'],
			[Event::INIT, 'init_1'],
			[Event::HOME_INIT, 'home_init'],
		];
	}

	/**
	 * @dataProvider getNamedEventData
	 */
	public function testOnNamedEventCallsHook($name, $expected): void
	{
		$event = new Event($name);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame('', $data);

			return $data;
		});

		HookEventBridge::onNamedEvent($event);
	}

	public static function getConfigLoadedEventData(): array
	{
		return [
			['test', 'test'],
			[ConfigLoadedEvent::CONFIG_LOADED, 'load_config'],
		];
	}

	/**
	 * @dataProvider getConfigLoadedEventData
	 */
	public function testOnConfigLoadedEventCallsHookWithCorrectValue($name, $expected): void
	{
		$config = $this->createStub(ConfigFileManager::class);

		$event = new ConfigLoadedEvent($name, $config);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected, $config) {
			$this->assertSame($expected, $name);
			$this->assertSame($config, $data);

			return $data;
		});

		HookEventBridge::onConfigLoadedEvent($event);
	}

	public static function getCollectRoutesEventData(): array
	{
		return [
			['test', 'test'],
			[CollectRoutesEvent::COLLECT_ROUTES, 'route_collection'],
		];
	}

	/**
	 * @dataProvider getCollectRoutesEventData
	 */
	public function testOnCollectRoutesEventCallsHookWithCorrectValue($name, $expected): void
	{
		$routeCollector = $this->createStub(RouteCollector::class);

		$event = new CollectRoutesEvent($name, $routeCollector);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected, $routeCollector) {
			$this->assertSame($expected, $name);
			$this->assertSame($routeCollector, $data);

			return $data;
		});

		HookEventBridge::onCollectRoutesEvent($event);
	}

	public function testOnPermissionTooltipContentEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT, ['model' => ['uid' => -1]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('lockview_content', $name);
			$this->assertSame(['uid' => -1], $data);

			return ['uid' => 123];
		});

		HookEventBridge::onPermissionTooltipContentEvent($event);

		$this->assertSame(
			['model' => ['uid' => 123]],
			$event->getArray(),
		);
	}

	public function testOnInsertPostLocalEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::INSERT_POST_LOCAL, ['item' => ['id' => -1]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('post_local', $name);
			$this->assertSame(['id' => -1], $data);

			return ['id' => 123];
		});

		HookEventBridge::onInsertPostLocalEvent($event);

		$this->assertSame(
			['item' => ['id' => 123]],
			$event->getArray(),
		);
	}

	public function testOnInsertPostLocalEndEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::INSERT_POST_LOCAL_END, ['item' => ['id' => -1]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('post_local_end', $name);
			$this->assertSame(['id' => -1], $data);

			return ['id' => 123];
		});

		HookEventBridge::onInsertPostLocalEndEvent($event);

		$this->assertSame(
			['item' => ['id' => 123]],
			$event->getArray(),
		);
	}

	public function testOnPreparePostStartEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::PREPARE_POST_START, ['item' => ['id' => -1]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('prepare_body_init', $name);
			$this->assertSame(['id' => -1], $data);

			return ['id' => 123];
		});

		HookEventBridge::onPreparePostStartEvent($event);

		$this->assertSame(
			['item' => ['id' => 123]],
			$event->getArray(),
		);
	}

	public function testOnPhotoUploadStartEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::PHOTO_UPLOAD_START, ['request' => ['album' => -1]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('photo_post_init', $name);
			$this->assertSame(['album' => -1], $data);

			return ['album' => 123];
		});

		HookEventBridge::onPhotoUploadStartEvent($event);

		$this->assertSame(
			['request' => ['album' => 123]],
			$event->getArray(),
		);
	}

	public function testOnPhotoUploadEndEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::PHOTO_UPLOAD_END, ['id' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('photo_post_end', $name);
			$this->assertSame(-1, $data);

			return 123;
		});

		HookEventBridge::onPhotoUploadEndEvent($event);
	}

	public function testOnProfileSidebarEntryEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::PROFILE_SIDEBAR_ENTRY, ['profile' => ['uid' => 0, 'name' => 'original']]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('profile_sidebar_enter', $name);
			$this->assertSame(['uid' => 0, 'name' => 'original'], $data);

			return ['uid' => 0, 'name' => 'changed'];
		});

		HookEventBridge::onProfileSidebarEntryEvent($event);

		$this->assertSame(
			['profile' => ['uid' => 0, 'name' => 'changed']],
			$event->getArray(),
		);
	}

	public function testOnOembedFetchEndEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::OEMBED_FETCH_END, ['url' => 'original_url']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('oembed_fetch_url', $name);
			$this->assertSame('original_url', $data);

			return 'changed_url';
		});

		HookEventBridge::onOembedFetchEndEvent($event);

		$this->assertSame(
			['url' => 'changed_url'],
			$event->getArray(),
		);
	}

	public function testOnBbcodeToHtmlEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::BBCODE_TO_HTML_START, ['bbcode2html' => '[b]original[/b]']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('bbcode', $name);
			$this->assertSame('[b]original[/b]', $data);

			return '<b>changed</b>';
		});

		HookEventBridge::onBbcodeToHtmlEvent($event);

		$this->assertSame(
			['bbcode2html' => '<b>changed</b>'],
			$event->getArray(),
		);
	}

	public function testOnHtmlToBbcodeEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::HTML_TO_BBCODE_END, ['html2bbcode' => '<b>original</b>']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('html2bbcode', $name);
			$this->assertSame('<b>original</b>', $data);

			return '[b]changed[/b]';
		});

		HookEventBridge::onHtmlToBbcodeEvent($event);

		$this->assertSame(
			['html2bbcode' => '[b]changed[/b]'],
			$event->getArray(),
		);
	}

	public function testOnBbcodeToMarkdownEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::BBCODE_TO_MARKDOWN_END, ['bbcode2markdown' => '[b]original[/b]']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('bb2diaspora', $name);
			$this->assertSame('[b]original[/b]', $data);

			return '**changed**';
		});

		HookEventBridge::onBbcodeToMarkdownEvent($event);

		$this->assertSame(
			['bbcode2markdown' => '**changed**'],
			$event->getArray(),
		);
	}

	public function testOnEventCreatedEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::EVENT_CREATED, ['event' => ['id' => 123]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('event_created', $name);
			$this->assertSame(123, $data);

			return 123;
		});

		HookEventBridge::onEventCreatedEvent($event);
	}

	public function testOnAccountRegisterEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::ACCOUNT_REGISTER, ['uid' => 123]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('register_account', $name);
			$this->assertSame(123, $data);

			return $data;
		});

		HookEventBridge::onAccountRegisterEvent($event);
	}

	public function testOnAccountRemoveEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::ACCOUNT_REMOVE, ['user' => ['uid' => 123]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('remove_user', $name);
			$this->assertSame(['uid' => 123], $data);

			return $data;
		});

		HookEventBridge::onAccountRemoveEvent($event);
	}

	public function testOnEventUpdatedEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::EVENT_UPDATED, ['event' => ['id' => 123]]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('event_updated', $name);
			$this->assertSame(123, $data);

			return 123;
		});

		HookEventBridge::onEventUpdatedEvent($event);
	}

	public static function getArrayFilterEventData(): array
	{
		return [
			['test', 'test'],
			[ArrayFilterEvent::APP_MENU, 'app_menu'],
			[ArrayFilterEvent::NAV_INFO, 'nav_info'],
			[ArrayFilterEvent::FEATURE_ENABLED, 'isEnabled'],
			[ArrayFilterEvent::FEATURE_GET, 'get'],
			[ArrayFilterEvent::INSERT_POST_LOCAL_START, 'post_local_start'],
			[ArrayFilterEvent::INSERT_POST_REMOTE, 'post_remote'],
			[ArrayFilterEvent::INSERT_POST_REMOTE_END, 'post_remote_end'],
			[ArrayFilterEvent::PREPARE_POST_FILTER_CONTENT, 'prepare_body_content_filter'],
			[ArrayFilterEvent::PREPARE_POST, 'prepare_body'],
			[ArrayFilterEvent::PREPARE_POST_END, 'prepare_body_final'],
			[ArrayFilterEvent::PHOTO_UPLOAD_FORM, 'photo_upload_form'],
			[ArrayFilterEvent::PHOTO_UPLOAD, 'photo_post_file'],
			[ArrayFilterEvent::NETWORK_TO_NAME, 'network_to_name'],
			[ArrayFilterEvent::NETWORK_CONTENT_START, 'network_content_init'],
			[ArrayFilterEvent::NETWORK_CONTENT_TABS, 'network_tabs'],
			[ArrayFilterEvent::PARSE_LINK, 'parse_link'],
			[ArrayFilterEvent::CONVERSATION_START, 'conversation_start'],
			[ArrayFilterEvent::FETCH_ITEM_BY_LINK, 'item_by_link'],
			[ArrayFilterEvent::ITEM_TAGGED, 'tagged'],
			[ArrayFilterEvent::DISPLAY_ITEM, 'display_item'],
			[ArrayFilterEvent::CACHE_ITEM, 'put_item_in_cache'],
			[ArrayFilterEvent::CHECK_ITEM_NOTIFICATION, 'check_item_notification'],
			[ArrayFilterEvent::ENOTIFY, 'enotify'],
			[ArrayFilterEvent::ENOTIFY_STORE, 'enotify_store'],
			[ArrayFilterEvent::ENOTIFY_MAIL, 'enotify_mail'],
			[ArrayFilterEvent::DETECT_LANGUAGES, 'detect_languages'],
			[ArrayFilterEvent::RENDER_LOCATION, 'render_location'],
			[ArrayFilterEvent::ITEM_PHOTO_MENU, 'item_photo_menu'],
			[ArrayFilterEvent::DIRECTORY_ITEM, 'directory_item'],
			[ArrayFilterEvent::CONTACT_PHOTO_MENU, 'contact_photo_menu'],
			[ArrayFilterEvent::PROFILE_SIDEBAR, 'profile_sidebar'],
			[ArrayFilterEvent::PROFILE_TABS, 'profile_tabs'],
			[ArrayFilterEvent::PROFILE_SETTINGS_FORM, 'profile_edit'],
			[ArrayFilterEvent::PROFILE_SETTINGS_POST, 'profile_post'],
			[ArrayFilterEvent::MODERATION_USERS_TABS, 'moderation_users_tabs'],
			[ArrayFilterEvent::ACL_LOOKUP_END, 'acl_lookup_end'],
			[ArrayFilterEvent::PAGE_INFO, 'page_info_data'],
			[ArrayFilterEvent::SMILEY_LIST, 'smilie'],
			[ArrayFilterEvent::JOT_NETWORKS, 'jot_networks'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW, 'support_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW, 'support_revoke_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE, 'support_probe'],
			[ArrayFilterEvent::FOLLOW_CONTACT, 'follow'],
			[ArrayFilterEvent::UNFOLLOW_CONTACT, 'unfollow'],
			[ArrayFilterEvent::REVOKE_FOLLOW_CONTACT, 'revoke_follow'],
			[ArrayFilterEvent::BLOCK_CONTACT, 'block'],
			[ArrayFilterEvent::UNBLOCK_CONTACT, 'unblock'],
			[ArrayFilterEvent::EDIT_CONTACT_FORM, 'contact_edit'],
			[ArrayFilterEvent::EDIT_CONTACT_POST, 'contact_edit_post'],
			[ArrayFilterEvent::AVATAR_LOOKUP, 'avatar_lookup'],
			[ArrayFilterEvent::ACCOUNT_AUTHENTICATE, 'authenticate'],
			[ArrayFilterEvent::ACCOUNT_REGISTER_FORM, 'register_form'],
			[ArrayFilterEvent::ACCOUNT_REGISTER_POST, 'register_post'],
			[ArrayFilterEvent::ACCOUNT_REGISTER, 'register_account'],
			[ArrayFilterEvent::ACCOUNT_REMOVE, 'remove_user'],
			[ArrayFilterEvent::EVENT_CREATED, 'event_created'],
			[ArrayFilterEvent::EVENT_UPDATED, 'event_updated'],
			[ArrayFilterEvent::ADD_WORKER_TASK, 'proc_run'],
			[ArrayFilterEvent::STORAGE_CONFIG, 'storage_config'],
			[ArrayFilterEvent::STORAGE_INSTANCE, 'storage_instance'],
			[ArrayFilterEvent::DB_STRUCTURE_DEFINITION, 'dbstructure_definition'],
			[ArrayFilterEvent::DB_VIEW_DEFINITION, 'dbview_definition'],
		];
	}

	/**
	 * @dataProvider getArrayFilterEventData
	 */
	public function testOnArrayFilterEventCallsHookWithCorrectValue($name, $expected): void
	{
		$event = new ArrayFilterEvent($name, ['original']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame(['original'], $data);

			return $data;
		});

		HookEventBridge::onArrayFilterEvent($event);
	}

	public static function getHtmlFilterEventData(): array
	{
		return [
			['test', 'test'],
			[HtmlFilterEvent::HEAD, 'head'],
			[HtmlFilterEvent::FOOTER, 'footer'],
			[HtmlFilterEvent::PAGE_HEADER, 'page_header'],
			[HtmlFilterEvent::PAGE_CONTENT_TOP, 'page_content_top'],
			[HtmlFilterEvent::PAGE_END, 'page_end'],
			[HtmlFilterEvent::MOD_HOME_CONTENT, 'home_content'],
			[HtmlFilterEvent::MOD_ABOUT_CONTENT, 'about_hook'],
			[HtmlFilterEvent::MOD_PROFILE_CONTENT, 'profile_advanced'],
			[HtmlFilterEvent::JOT_TOOL, 'jot_tool'],
			[HtmlFilterEvent::CONTACT_BLOCK_END, 'contact_block_end'],
		];
	}

	/**
	 * @dataProvider getHtmlFilterEventData
	 */
	public function testOnHtmlFilterEventCallsHookWithCorrectValue($name, $expected): void
	{
		$event = new HtmlFilterEvent($name, 'original');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setAccessible(true);

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame('original', $data);

			return $data;
		});

		HookEventBridge::onHtmlFilterEvent($event);
	}
}
