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
use Friendica\Event\BbcodeToHtmlStartEvent;
use Friendica\Event\BbcodeToMarkdownEndEvent;
use Friendica\Event\BlockContactEvent;
use Friendica\Event\CacheItemEvent;
use Friendica\Event\CheckItemNotificationEvent;
use Friendica\Event\ContactPhotoMenuEvent;
use Friendica\Event\ConversationStartEvent;
use Friendica\Event\DirectoryItemEvent;
use Friendica\Event\DisplayItemEvent;
use Friendica\Event\EnotifyEvent;
use Friendica\Event\EnotifyMailEvent;
use Friendica\Event\EnotifyStoreEvent;
use Friendica\Event\EditContactFormEvent;
use Friendica\Event\EditContactPostEvent;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Event\FollowContactEvent;
use Friendica\Event\HtmlToBbcodeEndEvent;
use Friendica\Event\InsertPostLocalEvent;
use Friendica\Event\ItemPhotoMenuEvent;
use Friendica\Event\ItemTaggedEvent;
use Friendica\Event\LoggedInEvent;
use Friendica\Event\LoginFormEvent;
use Friendica\Event\MagicAuthSuccessEvent;
use Friendica\Event\NetworkToNameEvent;
use Friendica\Event\NetworkContentStartEvent;
use Friendica\Event\NetworkContentTabsEvent;
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
use Friendica\Event\OcrDetectionEvent;
use Friendica\Event\PageInfoEvent;
use Friendica\Event\ParseLinkEvent;
use Friendica\Event\RenderLocationEvent;
use Friendica\Event\SmileyListEvent;
use Friendica\Event\TemplateVarsEvent;
use Friendica\Event\InsertPostLocalEndEvent;
use Friendica\Event\InsertPostLocalStartEvent;
use Friendica\Event\InsertPostRemoteEvent;
use Friendica\Event\InsertPostRemoteEndEvent;
use Friendica\Event\JotNetworksEvent;
use Friendica\Event\PreparePostEndEvent;
use Friendica\Event\PreparePostEvent;
use Friendica\Event\PreparePostFilterContentEvent;
use Friendica\Event\PreparePostStartEvent;
use Friendica\Event\PhotoUploadEndEvent;
use Friendica\Event\PhotoUploadFormEvent;
use Friendica\Event\ProfileSettingsFormEvent;
use Friendica\Event\ProfileSettingsPostEvent;
use Friendica\Event\ProfileSidebarEvent;
use Friendica\Event\ProfileSidebarStartEvent;
use Friendica\Event\ProfileTabsEvent;
use Friendica\Event\PhotoUploadEvent;
use Friendica\Event\PhotoUploadStartEvent;
use Friendica\Event\ModulePostRecipientEvent;
use Friendica\Event\ZrlInitEvent;
use Friendica\Event\UnfollowContactEvent;
use Friendica\Event\RevokeFollowContactEvent;
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
			BbcodeToHtmlStartEvent::NAME                      => 'onBbcodeToHtmlEvent',
			BbcodeToMarkdownEndEvent::NAME                    => 'onBbcodeToMarkdownEndEvent',
			BlockContactEvent::NAME                           => 'onBlockContactEvent',
			CacheItemEvent::NAME                              => 'onCacheItemEvent',
			CheckItemNotificationEvent::NAME                  => 'onCheckItemNotificationEvent',
			ArrayFilterEvent::CONNECTOR_SETTINGS_POST         => 'onArrayFilterEvent',
			ContactPhotoMenuEvent::NAME                       => 'onContactPhotoMenuEvent',
			ConversationStartEvent::NAME                      => 'onConversationStartEvent',
			ArrayFilterEvent::DB_STRUCTURE_DEFINITION         => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_VIEW_DEFINITION              => 'onArrayFilterEvent',
			ArrayFilterEvent::DETECT_LANGUAGES                => 'onArrayFilterEvent',
			DirectoryItemEvent::NAME                          => 'onDirectoryItemEvent',
			DisplayItemEvent::NAME                            => 'onDisplayItemEvent',
			ArrayFilterEvent::DISPLAY_SETTINGS_POST           => 'onArrayFilterEvent',
			EditContactFormEvent::NAME                        => 'onEditContactFormEvent',
			EditContactPostEvent::NAME                        => 'onEditContactPostEvent',
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
			FollowContactEvent::NAME                          => 'onFollowContactEvent',
			ArrayFilterEvent::GENERATE_MAP                    => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_NAMED_MAP              => 'onArrayFilterEvent',
			ArrayFilterEvent::GET_SITE_INFO                   => 'onArrayFilterEvent',
			ArrayFilterEvent::GLOBAL_DIR_UPDATE               => 'onArrayFilterEvent',
			HtmlToBbcodeEndEvent::NAME                        => 'onHtmlToBbcodeEvent',
			InsertPostLocalEvent::NAME                        => 'onInsertPostLocalEvent',
			InsertPostLocalEndEvent::NAME                     => 'onInsertPostLocalEndEvent',
			InsertPostRemoteEvent::NAME                       => 'onInsertPostRemoteEvent',
			InsertPostRemoteEndEvent::NAME                    => 'onInsertPostRemoteEndEvent',
			InsertPostLocalStartEvent::NAME                   => 'onInsertPostLocalStartEvent',
			ItemPhotoMenuEvent::NAME                          => 'onItemPhotoMenuEvent',
			ItemTaggedEvent::NAME                             => 'onItemTaggedEvent',
			JotNetworksEvent::NAME                            => 'onJotNetworksEvent',
			LoggedInEvent::NAME                               => 'onLoggedInEvent',
			LoginFormEvent::NAME                              => 'onLoginFormEvent',
			MagicAuthSuccessEvent::NAME                       => 'onMagicAuthSuccessEvent',
			ArrayFilterEvent::MAP_GET_COORDINATES             => 'onArrayFilterEvent',
			ArrayFilterEvent::MODERATION_USERS_TABS           => 'onArrayFilterEvent',
			ArrayFilterEvent::NAV_INFO                        => 'onArrayFilterEvent',
			NetworkContentStartEvent::NAME                    => 'onNetworkContentStartEvent',
			NetworkContentTabsEvent::NAME                     => 'onNetworkContentTabsEvent',
			NetworkToNameEvent::NAME                          => 'onNetworkToNameEvent',
			NotifierEndEvent::NAME                            => 'onNotifierEndEvent',
			OcrDetectionEvent::NAME                           => 'onOcrDetectionEvent',
			ArrayFilterEvent::OTHER_ENCAPSULATE               => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_UNENCAPSULATE             => 'onArrayFilterEvent',
			PageInfoEvent::NAME                               => 'onPageInfoEvent',
			ParseLinkEvent::NAME                              => 'onParseLinkEvent',
			ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT      => 'onPermissionTooltipContentEvent',
			PhotoUploadEvent::NAME                            => 'onPhotoUploadEvent',
			PhotoUploadEndEvent::NAME                         => 'onPhotoUploadEndEvent',
			PhotoUploadFormEvent::NAME                        => 'onPhotoUploadFormEvent',
			PhotoUploadStartEvent::NAME                       => 'onPhotoUploadStartEvent',
			PreparePostEvent::NAME                            => 'onPreparePostEvent',
			PreparePostEndEvent::NAME                         => 'onPreparePostEndEvent',
			PreparePostFilterContentEvent::NAME               => 'onPreparePostFilterContentEvent',
			PreparePostStartEvent::NAME                       => 'onPreparePostStartEvent',
			ArrayFilterEvent::PROBE_DETECT                    => 'onArrayFilterEvent',
			ProfileSettingsFormEvent::NAME                    => 'onProfileSettingsFormEvent',
			ProfileSettingsPostEvent::NAME                    => 'onProfileSettingsPostEvent',
			ProfileSidebarEvent::NAME                         => 'onProfileSidebarEvent',
			ProfileSidebarStartEvent::NAME                    => 'onProfileSidebarStartEvent',
			ProfileTabsEvent::NAME                            => 'onProfileTabsEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW        => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE         => 'onArrayFilterEvent',
			ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW => 'onArrayFilterEvent',
			RenderLocationEvent::NAME                         => 'onRenderLocationEvent',
			RevokeFollowContactEvent::NAME                    => 'onRevokeFollowContactEvent',
			SmileyListEvent::NAME                             => 'onSmileyListEvent',
			ArrayFilterEvent::STORAGE_CONFIG                  => 'onArrayFilterEvent',
			ArrayFilterEvent::STORAGE_INSTANCE                => 'onArrayFilterEvent',
			TemplateVarsEvent::NAME                           => 'onTemplateVarsEvent',
			ArrayFilterEvent::UNBLOCK_CONTACT                 => 'onArrayFilterEvent',
			UnfollowContactEvent::NAME                        => 'onUnfollowContactEvent',
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

	public function testOnEditContactFormEventCallsHookWithCorrectValue(): void
	{
		$event = new EditContactFormEvent(['name' => 'original'], '<p>original</p>');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('contact_edit', $name);
			$this->assertSame([
				'contact' => ['name' => 'original'],
				'output'  => '<p>original</p>',
			], $data);

			return [
				'contact' => ['name' => 'original'],
				'output'  => '<p>modified</p>',
			];
		});

		HookEventBridge::onEditContactFormEvent($event);

		$this->assertSame('<p>modified</p>', $event->getOutput());
	}

	public function testOnEditContactFormEventCallsSetterOnlyForValidOutput(): void
	{
		$event = new EditContactFormEvent(['name' => 'original'], '<p>original</p>');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			return [
				'contact' => ['name' => 'original'],
				'output'  => null,
			];
		});

		HookEventBridge::onEditContactFormEvent($event);

		$this->assertSame('<p>original</p>', $event->getOutput());
	}

	public function testOnEditContactPostEventCallsHookWithCorrectValue(): void
	{
		$event = new EditContactPostEvent(['hidden' => true]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('contact_edit_post', $name);
			$this->assertSame(['hidden' => true], $data);

			return ['hidden' => false];
		});

		HookEventBridge::onEditContactPostEvent($event);

		$this->assertSame(
			['hidden' => false],
			$event->getRequestArray(),
		);
	}

	public function testOnFollowContactEventCallsHookWithCorrectValue(): void
	{
		$event = new FollowContactEvent('https://example.com/profile', 42, []);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('follow', $name);
			$this->assertSame([
				'url'     => 'https://example.com/profile',
				'uid'     => 42,
				'contact' => [],
			], $data);

			return [
				'url'     => 'https://example.com/profile',
				'uid'     => 42,
				'contact' => ['name' => 'contact'],
			];
		});

		HookEventBridge::onFollowContactEvent($event);

		$this->assertFalse($event->isAborted());
		$this->assertSame(['name' => 'contact'], $event->getContactArray());
	}

	public function testOnFollowContactEventSetsAbortedOnEmptyHookData(): void
	{
		$event = new FollowContactEvent('https://example.com/profile', 42, []);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('follow', $name);

			return [];
		});

		HookEventBridge::onFollowContactEvent($event);

		$this->assertTrue($event->isAborted());
		$this->assertSame([], $event->getContactArray());
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

	public function testOnInsertPostLocalStartEventCallsHookWithCorrectValue(): void
	{
		$event = new InsertPostLocalStartEvent(['uid' => 1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('post_local_start', $name);
			$this->assertSame(['uid' => 1], $data);

			return ['uid' => 2];
		});

		HookEventBridge::onInsertPostLocalStartEvent($event);

		$this->assertSame(
			['uid' => 2],
			$event->getRequestArray(),
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

	public function testOnPhotoUploadFormEventCallsHookWithCorrectValue(): void
	{
		$event = new PhotoUploadFormEvent(['post_url' => '/photos', 'addon_text' => '', 'default_upload' => true]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('photo_upload_form', $name);
			$this->assertSame(['post_url' => '/photos', 'addon_text' => '', 'default_upload' => true], $data);

			return ['post_url' => '/photos', 'addon_text' => 'text', 'default_upload' => false];
		});

		HookEventBridge::onPhotoUploadFormEvent($event);

		$this->assertSame(
			['post_url' => '/photos', 'addon_text' => 'text', 'default_upload' => false],
			$event->getFormArray(),
		);
	}

	public function testOnOcrDetectionEventCallsHookWithCorrectValue(): void
	{
		$event = new OcrDetectionEvent('binary data');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('ocr-detection', $name);
			$this->assertSame(['img_str' => 'binary data', 'description' => null], $data);

			return ['img_str' => 'binary data', 'description' => 'A photo of a cat'];
		});

		HookEventBridge::onOcrDetectionEvent($event);

		$this->assertSame('A photo of a cat', $event->getDescription());
	}

	public function testOnNetworkToNameEventCallsHookWithCorrectValue(): void
	{
		$event = new NetworkToNameEvent(['dfrn' => 'DFRN']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('network_to_name', $name);
			$this->assertSame(['dfrn' => 'DFRN'], $data);

			return ['dfrn' => 'DFRN', 'feed' => 'RSS/Atom'];
		});

		HookEventBridge::onNetworkToNameEvent($event);

		$this->assertSame(['dfrn' => 'DFRN', 'feed' => 'RSS/Atom'], $event->getNetworks());
	}

	public function testOnNetworkContentStartEventCallsHookWithCorrectValue(): void
	{
		$event = new NetworkContentStartEvent('q=/network');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): void {
			$this->assertSame('network_content_init', $name);
			$this->assertSame(['query' => 'q=/network'], $data);
		});

		HookEventBridge::onNetworkContentStartEvent($event);
	}

	public function testOnNetworkContentTabsEventCallsHookWithCorrectValue(): void
	{
		$event = new NetworkContentTabsEvent([['code' => 'all', 'name' => 'All']]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('network_tabs', $name);
			$this->assertSame(['tabs' => [['code' => 'all', 'name' => 'All']]], $data);

			return ['tabs' => [['code' => 'all', 'name' => 'All'], ['code' => 'feed', 'name' => 'RSS']]];
		});

		HookEventBridge::onNetworkContentTabsEvent($event);

		$this->assertSame(
			[['code' => 'all', 'name' => 'All'], ['code' => 'feed', 'name' => 'RSS']],
			$event->getTabs(),
		);
	}

	public function testOnParseLinkEventCallsHookWithCorrectValue(): void
	{
		$event = new ParseLinkEvent('https://friendica.example', 'json');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('parse_link', $name);
			$this->assertSame([
				'url'    => 'https://friendica.example',
				'format' => 'json',
				'text'   => null,
			], $data);

			return [
				'url'    => 'https://friendica.example',
				'format' => 'json',
				'text'   => 'Some text',
			];
		});

		HookEventBridge::onParseLinkEvent($event);

		$this->assertSame('Some text', $event->getText());
	}

	public function testOnRenderLocationEventCallsHookWithCorrectValue(): void
	{
		$event = new RenderLocationEvent('Berlin', '52.52,13.405');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('render_location', $name);
			$this->assertSame([
				'location' => 'Berlin',
				'coord'    => '52.52,13.405',
				'html'     => '',
			], $data);

			return [
				'location' => 'Berlin',
				'coord'    => '52.52,13.405',
				'html'     => '<span>Berlin</span>',
			];
		});

		HookEventBridge::onRenderLocationEvent($event);

		$this->assertSame('<span>Berlin</span>', $event->getHtml());
	}

	public function testOnPageInfoEventCallsHookWithCorrectValue(): void
	{
		$event = new PageInfoEvent(['url' => 'https://example.com', 'type' => 'link']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('page_info_data', $name);
			$this->assertSame(['url' => 'https://example.com', 'type' => 'link'], $data);

			return ['url' => 'https://example.com', 'type' => 'photo'];
		});

		HookEventBridge::onPageInfoEvent($event);

		$this->assertSame(['url' => 'https://example.com', 'type' => 'photo'], $event->getDataArray());
	}

	public function testOnSmileyListEventCallsHookWithCorrectValue(): void
	{
		$event = new SmileyListEvent(['&lt;3'], ['<img src="heart.gif" />']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('smilie', $name);
			$this->assertSame([
				'texts' => ['&lt;3'],
				'icons' => ['<img src="heart.gif" />'],
			], $data);

			return [
				'texts' => ['&lt;3', ':-)'],
				'icons' => ['<img src="heart.gif" />', '<img src="smile.gif" />'],
			];
		});

		HookEventBridge::onSmileyListEvent($event);

		$this->assertSame(['&lt;3', ':-)'], $event->getTexts());
		$this->assertSame(['<img src="heart.gif" />', '<img src="smile.gif" />'], $event->getIcons());
	}

	public function testOnTemplateVarsEventCallsHookWithCorrectValue(): void
	{
		$event = new TemplateVarsEvent('test.tpl', ['foo' => 'bar']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('template_vars', $name);
			$this->assertSame([
				'template' => 'test.tpl',
				'vars'     => ['foo' => 'bar'],
			], $data);

			return [
				'template' => 'test.tpl',
				'vars'     => ['foo' => 'baz'],
			];
		});

		HookEventBridge::onTemplateVarsEvent($event);

		$this->assertSame(['foo' => 'baz'], $event->getVars());
	}

	public function testOnContactPhotoMenuEventCallsHookWithCorrectValue(): void
	{
		$event = new ContactPhotoMenuEvent(
			['id' => 1, 'name' => 'Alice'],
			['profile' => ['View Profile', 'https://example.com', true]],
		);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('contact_photo_menu', $name);
			$this->assertSame([
				'contact' => ['id' => 1, 'name' => 'Alice'],
				'menu'    => ['profile' => ['View Profile', 'https://example.com', true]],
			], $data);

			return [
				'contact' => ['id' => 1, 'name' => 'Alice'],
				'menu'    => ['profile' => ['View Profile', 'https://example.com', true], 'pm' => ['Message', 'https://example.com/pm', false]],
			];
		});

		HookEventBridge::onContactPhotoMenuEvent($event);

		$this->assertSame(
			['profile' => ['View Profile', 'https://example.com', true], 'pm' => ['Message', 'https://example.com/pm', false]],
			$event->getMenu(),
		);
	}

	public function testOnJotNetworksEventCallsHookWithCorrectValue(): void
	{
		$event = new JotNetworksEvent([['type' => 'checkbox']]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('jot_networks', $name);
			$this->assertSame([['type' => 'checkbox']], $data);

			return [['type' => 'checkbox'], ['type' => 'text']];
		});

		HookEventBridge::onJotNetworksEvent($event);

		$this->assertSame([['type' => 'checkbox'], ['type' => 'text']], $event->getJotnetsFields());
	}

	public function testOnPhotoUploadEndEventCallsHookWithCorrectValue(): void
	{
		$event = new PhotoUploadEndEvent('abc123');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, string $data): int {
			$this->assertSame('photo_post_end', $name);
			$this->assertSame('abc123', $data);

			return 123;
		});

		HookEventBridge::onPhotoUploadEndEvent($event);
	}

	public function testOnProfileSidebarStartEventCallsHookWithCorrectValue(): void
	{
		$event = new ProfileSidebarStartEvent(['uid' => 0, 'name' => 'original']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('profile_sidebar_enter', $name);
			$this->assertSame(['uid' => 0, 'name' => 'original'], $data);

			return ['uid' => 0, 'name' => 'changed'];
		});

		HookEventBridge::onProfileSidebarStartEvent($event);

		$this->assertSame(
			['uid' => 0, 'name' => 'changed'],
			$event->getProfileArray(),
		);
	}

	public function testOnProfileSidebarEventCallsHookWithCorrectValue(): void
	{
		$event = new ProfileSidebarEvent(['uid' => 0, 'name' => 'original'], '<p>entry</p>');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('profile_sidebar', $name);
			$this->assertSame([
				'profile' => ['uid' => 0, 'name' => 'original'],
				'entry'   => '<p>entry</p>',
			], $data);

			return [
				'profile' => ['uid' => 0, 'name' => 'changed'],
				'entry'   => '<p>modified</p>',
			];
		});

		HookEventBridge::onProfileSidebarEvent($event);

		$this->assertSame(
			['uid' => 0, 'name' => 'original'],
			$event->getProfileArray(),
		);
		$this->assertSame('<p>modified</p>', $event->getEntry());
	}

	public function testOnProfileTabsEventCallsHookWithCorrectValue(): void
	{
		$event = new ProfileTabsEvent(
			true,
			'testnick',
			'status',
			[['label' => 'Posts', 'url' => '/profile/testnick/conversations', 'sel' => 'active', 'title' => 'All posts', 'id' => 'status-tab', 'accesskey' => 'm']],
		);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('profile_tabs', $name);
			$this->assertSame([
				'is_owner' => true,
				'nickname' => 'testnick',
				'tab'      => 'status',
				'tabs'     => [['label' => 'Posts', 'url' => '/profile/testnick/conversations', 'sel' => 'active', 'title' => 'All posts', 'id' => 'status-tab', 'accesskey' => 'm']],
			], $data);

			return [
				'is_owner' => true,
				'nickname' => 'testnick',
				'tab'      => 'status',
				'tabs'     => [['label' => 'Other', 'url' => '/profile/testnick/other', 'sel' => '', 'title' => 'Other', 'id' => 'other-tab', 'accesskey' => 'o']],
			];
		});

		HookEventBridge::onProfileTabsEvent($event);

		$this->assertSame(
			[['label' => 'Other', 'url' => '/profile/testnick/other', 'sel' => '', 'title' => 'Other', 'id' => 'other-tab', 'accesskey' => 'o']],
			$event->getTabsArray(),
		);
	}

	public function testOnProfileSettingsFormEventCallsHookWithCorrectValue(): void
	{
		$event = new ProfileSettingsFormEvent(['name' => 'original'], '<p>original</p>');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('profile_edit', $name);
			$this->assertSame([
				'profile' => ['name' => 'original'],
				'entry'   => '<p>original</p>',
			], $data);

			return [
				'profile' => ['name' => 'original'],
				'entry'   => '<p>modified</p>',
			];
		});

		HookEventBridge::onProfileSettingsFormEvent($event);

		$this->assertSame('<p>modified</p>', $event->getEntry());
	}

	public function testOnProfileSettingsFormEventCallsSetterOnlyForValidEntry(): void
	{
		$event = new ProfileSettingsFormEvent(['name' => 'original'], '<p>original</p>');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			return [
				'profile' => ['name' => 'original'],
				'entry'   => null,
			];
		});

		HookEventBridge::onProfileSettingsFormEvent($event);

		$this->assertSame('<p>original</p>', $event->getEntry());
	}

	public function testOnProfileSettingsPostEventCallsHookWithCorrectValue(): void
	{
		$event = new ProfileSettingsPostEvent(['name' => 'original']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('profile_post', $name);
			$this->assertSame(['name' => 'original'], $data);

			return ['name' => 'modified'];
		});

		HookEventBridge::onProfileSettingsPostEvent($event);

		$this->assertSame(
			['name' => 'modified'],
			$event->getRequestArray(),
		);
	}

	public function testOnBbcodeToHtmlEventCallsHookWithCorrectValue(): void
	{
		$event = new BbcodeToHtmlStartEvent('[b]original[/b]');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('bbcode', $name);
			$this->assertSame('[b]original[/b]', $data);

			return '<b>changed</b>';
		});

		HookEventBridge::onBbcodeToHtmlEvent($event);

		$this->assertSame(
			'<b>changed</b>',
			$event->getBbcode2html(),
		);
	}

	public function testOnHtmlToBbcodeEventCallsHookWithCorrectValue(): void
	{
		$event = new HtmlToBbcodeEndEvent('<b>original</b>');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('html2bbcode', $name);
			$this->assertSame('<b>original</b>', $data);

			return '[b]changed[/b]';
		});

		HookEventBridge::onHtmlToBbcodeEvent($event);

		$this->assertSame(
			'[b]changed[/b]',
			$event->getHtml2bbcode(),
		);
	}

	public function testOnBbcodeToMarkdownEventCallsHookWithCorrectValue(): void
	{
		$event = new BbcodeToMarkdownEndEvent('[b]original[/b]');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, string $data): string {
			$this->assertSame('bb2diaspora', $name);
			$this->assertSame('[b]original[/b]', $data);

			return '**changed**';
		});

		HookEventBridge::onBbcodeToMarkdownEndEvent($event);

		$this->assertSame(
			'**changed**',
			$event->getBbcode2markdown(),
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
			[PhotoUploadEvent::NAME, 'photo_post_file'],
			[NetworkToNameEvent::NAME, 'network_to_name'],
			[NetworkContentStartEvent::NAME, 'network_content_init'],
			[NetworkContentTabsEvent::NAME, 'network_tabs'],
			[ParseLinkEvent::NAME, 'parse_link'],
			[EnotifyEvent::NAME, 'enotify'],
			[EnotifyMailEvent::NAME, 'enotify_mail'],
			[EnotifyStoreEvent::NAME, 'enotify_store'],
			[ArrayFilterEvent::DETECT_LANGUAGES, 'detect_languages'],
			[RenderLocationEvent::NAME, 'render_location'],
			[ContactPhotoMenuEvent::NAME, 'contact_photo_menu'],
			[ProfileSettingsFormEvent::NAME, 'profile_edit'],
			[ProfileSettingsPostEvent::NAME, 'profile_post'],
			[ArrayFilterEvent::MODERATION_USERS_TABS, 'moderation_users_tabs'],
			[ArrayFilterEvent::ACL_LOOKUP_END, 'acl_lookup_end'],
			[PageInfoEvent::NAME, 'page_info_data'],
			[SmileyListEvent::NAME, 'smilie'],
			[JotNetworksEvent::NAME, 'jot_networks'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW, 'support_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW, 'support_revoke_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE, 'support_probe'],
			[FollowContactEvent::NAME, 'follow'],
			[UnfollowContactEvent::NAME, 'unfollow'],
			[RevokeFollowContactEvent::NAME, 'revoke_follow'],
			[BlockContactEvent::NAME, 'block'],
			[ArrayFilterEvent::UNBLOCK_CONTACT, 'unblock'],
			[EditContactFormEvent::NAME, 'contact_edit'],
			[EditContactPostEvent::NAME, 'contact_edit_post'],
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
