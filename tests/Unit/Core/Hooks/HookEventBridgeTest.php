<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Hooks;

use FastRoute\RouteCollector;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Hooks\HookEventBridge;
use Friendica\Event\AccountAuthenticateEvent;
use Friendica\Event\AccountRegisterEvent;
use Friendica\Event\AccountRegisterFormEvent;
use Friendica\Event\AccountRegisterPostEvent;
use Friendica\Event\AccountRemoveEvent;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\CacheItemEvent;
use Friendica\Event\CheckItemNotificationEvent;
use Friendica\Event\ConversationStartEvent;
use Friendica\Event\DirectoryItemEvent;
use Friendica\Event\DisplayItemEvent;
use Friendica\Event\EnotifyEvent;
use Friendica\Event\EnotifyMailEvent;
use Friendica\Event\EnotifyStoreEvent;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Event\InsertPostLocalEvent;
use Friendica\Event\ItemPhotoMenuEvent;
use Friendica\Event\ItemTaggedEvent;
use Friendica\Event\LoggedInEvent;
use Friendica\Event\LoginFormEvent;
use Friendica\Event\MagicAuthSuccessEvent;
use Friendica\Event\CollectRoutesEvent;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\HomeInitEvent;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Event\InitEvent;
use Friendica\Event\LoggingOutEvent;
use Friendica\Event\ModuleContentEvent;
use Friendica\Event\ModuleInitEvent;
use Friendica\Event\ModulePostEvent;
use Friendica\Event\NotifierEndEvent;
use Friendica\Event\InsertPostLocalEndEvent;
use Friendica\Event\InsertPostRemoteEvent;
use Friendica\Event\InsertPostRemoteEndEvent;
use Friendica\Event\PreparePostEndEvent;
use Friendica\Event\PreparePostEvent;
use Friendica\Event\PreparePostFilterContentEvent;
use Friendica\Event\PreparePostStartEvent;
use Friendica\Event\PhotoUploadEvent;
use Friendica\Event\PhotoUploadStartEvent;
use Friendica\Event\ModulePostRecipientEvent;
use Friendica\Event\ZrlInitEvent;
use PHPUnit\Framework\TestCase;

class HookEventBridgeTest extends TestCase
{
	protected function tearDown(): void
	{
		// Reset the mocked Hook call to prevent it from leaking into other tests
		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');
		$reflectionProperty->setValue(null, null);

		parent::tearDown();
	}

	public function testGetStaticSubscribedEventsReturnsStaticMethods(): void
	{
		$expected = [
			InitEvent::NAME                                   => 'onNamedEvent',
			HomeInitEvent::NAME                               => 'onNamedEvent',
			LoggingOutEvent::NAME                             => 'onNamedEvent',
			ConfigLoadedEvent::NAME                           => 'onConfigLoadedEvent',
			CollectRoutesEvent::NAME                          => 'onCollectRoutesEvent',
			AccountAuthenticateEvent::NAME                    => 'onAccountAuthenticateEvent',
			AccountRegisterEvent::NAME                        => 'onAccountRegisterEvent',
			AccountRegisterFormEvent::NAME                    => 'onAccountRegisterFormEvent',
			AccountRegisterPostEvent::NAME                    => 'onAccountRegisterPostEvent',
			AccountRemoveEvent::NAME                          => 'onAccountRemoveEvent',
			ArrayFilterEvent::ACL_LOOKUP_END                  => 'onArrayFilterEvent',
			ArrayFilterEvent::ADD_WORKER_TASK                 => 'onArrayFilterEvent',
			ArrayFilterEvent::ADDON_SETTINGS_POST             => 'onArrayFilterEvent',
			ArrayFilterEvent::APP_MENU                        => 'onArrayFilterEvent',
			ArrayFilterEvent::AVATAR_LOOKUP                   => 'onArrayFilterEvent',
			ArrayFilterEvent::BBCODE_TO_HTML_START            => 'onBbcodeToHtmlEvent',
			ArrayFilterEvent::BBCODE_TO_MARKDOWN_END          => 'onBbcodeToMarkdownEvent',
			ArrayFilterEvent::BLOCK_CONTACT                   => 'onArrayFilterEvent',
			CacheItemEvent::NAME                              => 'onCacheItemEvent',
			CheckItemNotificationEvent::NAME                  => 'onCheckItemNotificationEvent',
			ArrayFilterEvent::CONNECTOR_SETTINGS_POST         => 'onArrayFilterEvent',
			ArrayFilterEvent::CONTACT_PHOTO_MENU              => 'onArrayFilterEvent',
			ConversationStartEvent::NAME                      => 'onConversationStartEvent',
			ArrayFilterEvent::DB_STRUCTURE_DEFINITION         => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_VIEW_DEFINITION              => 'onArrayFilterEvent',
			ArrayFilterEvent::DETECT_LANGUAGES                => 'onArrayFilterEvent',
			DirectoryItemEvent::NAME                          => 'onDirectoryItemEvent',
			DisplayItemEvent::NAME                            => 'onDisplayItemEvent',
			ArrayFilterEvent::DISPLAY_SETTINGS_POST           => 'onArrayFilterEvent',
			ArrayFilterEvent::EDIT_CONTACT_FORM               => 'onArrayFilterEvent',
			ArrayFilterEvent::EDIT_CONTACT_POST               => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE               => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE_END           => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAILER_SEND                    => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAILER_SEND_PREPARE            => 'onEmailerSendPrepareEvent',
			EnotifyEvent::NAME                                => 'onEnotifyEvent',
			EnotifyMailEvent::NAME                            => 'onEnotifyMailEvent',
			EnotifyStoreEvent::NAME                           => 'onEnotifyStoreEvent',
			ArrayFilterEvent::EVENT_CREATED                   => 'onEventCreatedEvent',
			ArrayFilterEvent::EVENT_UPDATED                   => 'onEventUpdatedEvent',
			ArrayFilterEvent::FEATURE_ENABLED                 => 'onArrayFilterEvent',
			ArrayFilterEvent::FEATURE_GET                     => 'onArrayFilterEvent',
			FetchItemByLinkEvent::NAME                        => 'onFetchItemByLinkEvent',
			ArrayFilterEvent::FOLLOW_CONTACT                  => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_MAP                    => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_NAMED_MAP              => 'onArrayFilterEvent',
			ArrayFilterEvent::GET_SITE_INFO                   => 'onArrayFilterEvent',
			ArrayFilterEvent::GLOBAL_DIR_UPDATE               => 'onArrayFilterEvent',
			ArrayFilterEvent::HTML_TO_BBCODE_END              => 'onHtmlToBbcodeEvent',
			InsertPostLocalEvent::NAME                        => 'onInsertPostLocalEvent',
			InsertPostLocalEndEvent::NAME                     => 'onInsertPostLocalEndEvent',
			InsertPostRemoteEvent::NAME                       => 'onInsertPostRemoteEvent',
			InsertPostRemoteEndEvent::NAME                    => 'onInsertPostRemoteEndEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL_START         => 'onArrayFilterEvent',
			ItemPhotoMenuEvent::NAME                          => 'onItemPhotoMenuEvent',
			ItemTaggedEvent::NAME                             => 'onItemTaggedEvent',
			ArrayFilterEvent::JOT_NETWORKS                    => 'onArrayFilterEvent',
			LoggedInEvent::NAME                               => 'onLoggedInEvent',
			LoginFormEvent::NAME                              => 'onLoginFormEvent',
			MagicAuthSuccessEvent::NAME                       => 'onMagicAuthSuccessEvent',
			ArrayFilterEvent::MAP_GET_COORDINATES             => 'onArrayFilterEvent',
			ArrayFilterEvent::MODERATION_USERS_TABS           => 'onArrayFilterEvent',
			ArrayFilterEvent::NAV_INFO                        => 'onArrayFilterEvent',
			ArrayFilterEvent::NETWORK_CONTENT_START           => 'onArrayFilterEvent',
			ArrayFilterEvent::NETWORK_CONTENT_TABS            => 'onArrayFilterEvent',
			ArrayFilterEvent::NETWORK_TO_NAME                 => 'onArrayFilterEvent',
			NotifierEndEvent::NAME                            => 'onNotifierEndEvent',
			ArrayFilterEvent::OCR_DETECTION                   => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_ENCAPSULATE               => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_UNENCAPSULATE             => 'onArrayFilterEvent',
			ArrayFilterEvent::PAGE_INFO                       => 'onArrayFilterEvent',
			ArrayFilterEvent::PARSE_LINK                      => 'onArrayFilterEvent',
			ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT      => 'onPermissionTooltipContentEvent',
			PhotoUploadEvent::NAME                            => 'onPhotoUploadEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_END                => 'onPhotoUploadEndEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'onArrayFilterEvent',
			PhotoUploadStartEvent::NAME                       => 'onPhotoUploadStartEvent',
			PreparePostEvent::NAME                            => 'onPreparePostEvent',
			PreparePostEndEvent::NAME                         => 'onPreparePostEndEvent',
			PreparePostFilterContentEvent::NAME               => 'onPreparePostFilterContentEvent',
			PreparePostStartEvent::NAME                       => 'onPreparePostStartEvent',
			ArrayFilterEvent::PROBE_DETECT                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SETTINGS_FORM           => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SETTINGS_POST           => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SIDEBAR                 => 'onArrayFilterEvent',
			ArrayFilterEvent::PROFILE_SIDEBAR_ENTRY           => 'onProfileSidebarEntryEvent',
			ArrayFilterEvent::PROFILE_TABS                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW        => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE         => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW => 'onArrayFilterEvent',
			ArrayFilterEvent::RENDER_LOCATION                 => 'onArrayFilterEvent',
			ArrayFilterEvent::REVOKE_FOLLOW_CONTACT           => 'onArrayFilterEvent',
			ArrayFilterEvent::SMILEY_LIST                     => 'onArrayFilterEvent',
			ArrayFilterEvent::STORAGE_CONFIG                  => 'onArrayFilterEvent',
			ArrayFilterEvent::STORAGE_INSTANCE                => 'onArrayFilterEvent',
			ArrayFilterEvent::TEMPLATE_VARS                   => 'onArrayFilterEvent',
			ArrayFilterEvent::UNBLOCK_CONTACT                 => 'onArrayFilterEvent',
			ArrayFilterEvent::UNFOLLOW_CONTACT                => 'onArrayFilterEvent',
			ArrayFilterEvent::USER_EXPORT_OPTIONS             => 'onArrayFilterEvent',
			ZrlInitEvent::NAME                                => 'onZrlInitEvent',
			HtmlFilterEvent::CONTACT_BLOCK_END                => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER                           => 'onHtmlFilterEvent',
			HtmlFilterEvent::HEAD                             => 'onHtmlFilterEvent',
			HtmlFilterEvent::JOT_TOOL                         => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_ABOUT_CONTENT                => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_HOME_CONTENT                 => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_PROFILE_CONTENT              => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP                 => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END                         => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_HEADER                      => 'onHtmlFilterEvent',
			ModuleContentEvent::NAME                          => 'onModuleContentEvent',
			ModuleInitEvent::NAME                             => 'onModuleInitEvent',
			ModulePostEvent::NAME                             => 'onModulePostEvent',
			ModulePostRecipientEvent::NAME                    => 'onModulePostRecipientEvent',
		];

		$this->assertSame(
			$expected,
			HookEventBridge::getStaticSubscribedEvents(),
		);

		foreach ($expected as $methodName) {
			$this->assertTrue(
				method_exists(HookEventBridge::class, $methodName),
				$methodName . '() is not defined',
			);

			$this->assertTrue(
				(new \ReflectionMethod(HookEventBridge::class, $methodName))->isStatic(),
				$methodName . '() is not static',
			);
		}
	}

	public static function getNamedEventData(): array
	{
		return [
			[InitEvent::NAME, 'init_1'],
			[HomeInitEvent::NAME, 'home_init'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getNamedEventData')]
	public function testOnNamedEventCallsHook($name, $expected): void
	{
		$event = new class ($name) extends \Friendica\Core\Event\AbstractEvent {
			public function __construct(string $name)
			{
				parent::__construct($name);
			}
		};

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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
			['load_config'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getConfigLoadedEventData')]
	public function testOnConfigLoadedEventCallsHookWithCorrectValue(string $expected): void
	{
		$config = $this->createStub(ConfigFileManager::class);

		$event = new ConfigLoadedEvent($config);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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
			['route_collection'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getCollectRoutesEventData')]
	public function testOnCollectRoutesEventCallsHookWithCorrectValue(string $expected): void
	{
		$routeCollector = $this->createStub(RouteCollector::class);

		$event = new CollectRoutesEvent($routeCollector);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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
		$event = new InsertPostLocalEvent(['id' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('post_local', $name);
			$this->assertSame(['id' => -1], $data);

			return ['id' => 123];
		});

		HookEventBridge::onInsertPostLocalEvent($event);

		$this->assertSame(
			['id' => 123],
			$event->getItemArray(),
		);
	}

	public function testOnInsertPostLocalEndEventCallsHookWithCorrectValue(): void
	{
		$event = new InsertPostLocalEndEvent(['id' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('post_local_end', $name);
			$this->assertSame(['id' => -1], $data);

			return ['id' => 123];
		});

		HookEventBridge::onInsertPostLocalEndEvent($event);

		$this->assertSame(
			['id' => 123],
			$event->getItemArray(),
		);
	}

	public function testOnPreparePostStartEventCallsHookWithCorrectValue(): void
	{
		$event = new PreparePostStartEvent(['id' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('prepare_body_init', $name);
			$this->assertSame(['id' => -1], $data);

			return ['id' => 123];
		});

		HookEventBridge::onPreparePostStartEvent($event);

		$this->assertSame(
			['id' => 123],
			$event->getItemArray(),
		);
	}

	public function testOnPhotoUploadStartEventCallsHookWithCorrectValue(): void
	{
		$event = new PhotoUploadStartEvent(['album' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('photo_post_init', $name);
			$this->assertSame(['album' => -1], $data);

			return ['album' => 123];
		});

		HookEventBridge::onPhotoUploadStartEvent($event);

		$this->assertSame(
			['album' => 123],
			$event->getRequestArray(),
		);
	}

	public function testOnPhotoUploadEndEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::PHOTO_UPLOAD_END, ['id' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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

	public function testOnBbcodeToHtmlEventCallsHookWithCorrectValue(): void
	{
		$event = new ArrayFilterEvent(ArrayFilterEvent::BBCODE_TO_HTML_START, ['bbcode2html' => '[b]original[/b]']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('event_created', $name);
			$this->assertSame(123, $data);

			return 123;
		});

		HookEventBridge::onEventCreatedEvent($event);
	}

	public function testOnAccountRegisterEventCallsHookWithCorrectValue(): void
	{
		$event = new AccountRegisterEvent(123);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('register_account', $name);
			$this->assertSame(123, $data);

			return $data;
		});

		HookEventBridge::onAccountRegisterEvent($event);
	}

	public function testOnAccountRemoveEventCallsHookWithCorrectValue(): void
	{
		$event = new AccountRemoveEvent(['uid' => 123]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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

		$reflectionProperty->setValue(null, function (string $name, int $data): int {
			$this->assertSame('event_updated', $name);
			$this->assertSame(123, $data);

			return 123;
		});

		HookEventBridge::onEventUpdatedEvent($event);
	}

	public function testOnMagicAuthSuccessEventCallsHookWithCorrectValue(): void
	{
		$visitor = ['id' => 42, 'name' => 'TestVisitor'];
		$event   = new MagicAuthSuccessEvent($visitor, 'test=query');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('magic_auth_success', $name);
			$this->assertSame(['id' => 42, 'name' => 'TestVisitor'], $data['visitor']);
			$this->assertSame('test=query', $data['url']);

			$data['visitor']['id'] = 99;

			return $data;
		});

		HookEventBridge::onMagicAuthSuccessEvent($event);

		$this->assertSame(99, $event->getVisitorArray()['id']);
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
			[ArrayFilterEvent::PHOTO_UPLOAD_FORM, 'photo_upload_form'],
			[PhotoUploadEvent::NAME, 'photo_post_file'],
			[ArrayFilterEvent::NETWORK_TO_NAME, 'network_to_name'],
			[ArrayFilterEvent::NETWORK_CONTENT_START, 'network_content_init'],
			[ArrayFilterEvent::NETWORK_CONTENT_TABS, 'network_tabs'],
			[ArrayFilterEvent::PARSE_LINK, 'parse_link'],
			[EnotifyEvent::NAME, 'enotify'],
			[EnotifyMailEvent::NAME, 'enotify_mail'],
			[EnotifyStoreEvent::NAME, 'enotify_store'],
			[ArrayFilterEvent::DETECT_LANGUAGES, 'detect_languages'],
			[ArrayFilterEvent::RENDER_LOCATION, 'render_location'],
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
			[AccountAuthenticateEvent::NAME, 'authenticate'],
			[AccountRegisterFormEvent::NAME, 'register_form'],
			[AccountRegisterPostEvent::NAME, 'register_post'],
			[AccountRegisterEvent::NAME, 'register_account'],
			[ArrayFilterEvent::EVENT_CREATED, 'event_created'],
			[ArrayFilterEvent::EVENT_UPDATED, 'event_updated'],
			[ArrayFilterEvent::ADD_WORKER_TASK, 'proc_run'],
			[ArrayFilterEvent::STORAGE_CONFIG, 'storage_config'],
			[ArrayFilterEvent::STORAGE_INSTANCE, 'storage_instance'],
			[ArrayFilterEvent::DB_STRUCTURE_DEFINITION, 'dbstructure_definition'],
			[ArrayFilterEvent::DB_VIEW_DEFINITION, 'dbview_definition'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getArrayFilterEventData')]
	public function testOnArrayFilterEventCallsHookWithCorrectValue($name, $expected): void
	{
		$event = new ArrayFilterEvent($name, ['original']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

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

	#[\PHPUnit\Framework\Attributes\DataProvider('getHtmlFilterEventData')]
	public function testOnHtmlFilterEventCallsHookWithCorrectValue($name, $expected): void
	{
		$event = new HtmlFilterEvent($name, 'original');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame('original', $data);

			return $data;
		});

		HookEventBridge::onHtmlFilterEvent($event);
	}

	public static function getModuleInitEventData(): array
	{
		return [
			'Home'         => ['home_mod_init', 'home', \Friendica\Module\Home::class],
			'LegacyModule' => ['photos_mod_init', 'photos', \Friendica\LegacyModule::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getModuleInitEventData')]
	public function testOnModuleInitEventCallsHookWithCorrectValue($expected, $moduleName, $moduleClass): void
	{
		$event = new ModuleInitEvent($moduleName, $moduleClass);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame('', $data);

			return $data;
		});

		HookEventBridge::onModuleInitEvent($event);
	}

	public static function getModulePostEventData(): array
	{
		return [
			'Home'         => ['home_mod_post', 'home', \Friendica\Module\Home::class],
			'LegacyModule' => ['photos_mod_post', 'photos', \Friendica\LegacyModule::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getModulePostEventData')]
	public function testOnModulePostEventCallsHookWithCorrectValue($expected, $moduleName, $moduleClass): void
	{
		$event = new ModulePostEvent($moduleName, $moduleClass, ['original']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame(['original'], $data);

			return $data;
		});

		HookEventBridge::onModulePostEvent($event);
	}

	public static function getModuleContentEventData(): array
	{
		return [
			'Home'         => ['Friendica\Module\Home_mod_content', 'home', \Friendica\Module\Home::class],
			'LegacyModule' => ['Friendica\LegacyModule_mod_content', 'photos', \Friendica\LegacyModule::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getModuleContentEventData')]
	public function testOnModuleContentEventCallsHookWithCorrectValue($expected, $moduleName, $moduleClass): void
	{
		$event = new ModuleContentEvent($moduleName, $moduleClass, 'original');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame(['content' => 'original'], $data);

			$data['content'] = 'changed';

			return $data;
		});

		HookEventBridge::onModuleContentEvent($event);

		$this->assertSame('changed', $event->getContent());
	}

	public static function getModulePostRecipientEventData(): array
	{
		return [
			'Home'         => ['home_post_recipient', 'home', \Friendica\Module\Home::class],
			'LegacyModule' => ['photos_post_recipient', 'photos', \Friendica\LegacyModule::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getModulePostRecipientEventData')]
	public function testOnModulePostRecipientEventCallsHookWithCorrectValue($expected, $moduleName, $moduleClass): void
	{
		$event = new ModulePostRecipientEvent($moduleName, $moduleClass, 'original');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, $data) use ($expected) {
			$this->assertSame($expected, $name);
			$this->assertSame('original', $data);

			return $data;
		});

		HookEventBridge::onModulePostRecipientEvent($event);
	}
}
