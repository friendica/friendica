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
use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Core\Worker;
use Friendica\Event\AccountAuthenticateEvent;
use Friendica\Event\AccountRegisterEvent;
use Friendica\Event\AccountRegisterFormEvent;
use Friendica\Event\AccountRegisterPostEvent;
use Friendica\Event\AccountRemoveEvent;
use Friendica\Event\AclLookupEndEvent;
use Friendica\Event\AddonSettingsPostEvent;
use Friendica\Event\AddWorkerTaskEvent;
use Friendica\Event\AppMenuEvent;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\AvatarLookupEvent;
use Friendica\Event\BbcodeToHtmlStartEvent;
use Friendica\Event\BbcodeToMarkdownEndEvent;
use Friendica\Event\BlockContactEvent;
use Friendica\Event\CacheItemEvent;
use Friendica\Event\CheckItemNotificationEvent;
use Friendica\Event\ConnectorSettingsPostEvent;
use Friendica\Event\ContactPhotoMenuEvent;
use Friendica\Event\ConversationStartEvent;
use Friendica\Event\DbStructureDefinitionEvent;
use Friendica\Event\DbViewDefinitionEvent;
use Friendica\Event\DetectLanguagesEvent;
use Friendica\Event\DirectoryItemEvent;
use Friendica\Event\DisplayItemEvent;
use Friendica\Event\DisplaySettingsPostEvent;
use Friendica\Event\EmailGetMessageEvent;
use Friendica\Event\EmailerSendEvent;
use Friendica\Event\EmailerSendPrepareEvent;
use Friendica\Event\EnotifyEvent;
use Friendica\Event\EnotifyMailEvent;
use Friendica\Event\EnotifyStoreEvent;
use Friendica\Event\EditContactFormEvent;
use Friendica\Event\EditContactPostEvent;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Event\FeatureEnabledEvent;
use Friendica\Event\FeatureGetEvent;
use Friendica\Event\FollowContactEvent;
use Friendica\Object\EMail\IEmail;
use Friendica\Event\GetSiteInfoEvent;
use Friendica\Event\GlobalDirUpdateEvent;
use Friendica\Event\HtmlToBbcodeEndEvent;
use Friendica\Event\InsertPostLocalEvent;
use Friendica\Event\ItemPhotoMenuEvent;
use Friendica\Event\ItemTaggedEvent;
use Friendica\Event\LoggedInEvent;
use Friendica\Event\LoginFormEvent;
use Friendica\Event\MagicAuthSuccessEvent;
use Friendica\Event\ModerationUsersTabsEvent;
use Friendica\Event\NavInfoEvent;
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
use Friendica\Event\PermissionTooltipContentEvent;
use Friendica\Event\RenderLocationEvent;
use Friendica\Event\SmileyListEvent;
use Friendica\Event\StorageConfigEvent;
use Friendica\Event\StorageInstanceEvent;
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
use Friendica\Event\ProtocolSupportsFollowEvent;
use Friendica\Event\ProtocolSupportsProbeEvent;
use Friendica\Event\ProtocolSupportsRevokeFollowEvent;
use Friendica\Event\PhotoUploadEvent;
use Friendica\Event\PhotoUploadStartEvent;
use Friendica\Event\ProbeDetectEvent;
use Friendica\Event\ModulePostRecipientEvent;
use Friendica\Event\ZrlInitEvent;
use Friendica\Event\UnblockContactEvent;
use Friendica\Event\UnfollowContactEvent;
use Friendica\Event\UserExportOptionsEvent;
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
			InitEvent::NAME                         => 'onNamedEvent',
			HomeInitEvent::NAME                     => 'onNamedEvent',
			LoggingOutEvent::NAME                   => 'onNamedEvent',
			ConfigLoadedEvent::NAME                 => 'onConfigLoadedEvent',
			CollectRoutesEvent::NAME                => 'onCollectRoutesEvent',
			AccountAuthenticateEvent::NAME          => 'onAccountAuthenticateEvent',
			AccountRegisterEvent::NAME              => 'onAccountRegisterEvent',
			AccountRegisterFormEvent::NAME          => 'onAccountRegisterFormEvent',
			AccountRegisterPostEvent::NAME          => 'onAccountRegisterPostEvent',
			AccountRemoveEvent::NAME                => 'onAccountRemoveEvent',
			AclLookupEndEvent::NAME                 => 'onAclLookupEndEvent',
			AddWorkerTaskEvent::NAME                => 'onAddWorkerTaskEvent',
			AddonSettingsPostEvent::NAME            => 'onAddonSettingsPostEvent',
			AppMenuEvent::NAME                      => 'onAppMenuEvent',
			AvatarLookupEvent::NAME                 => 'onAvatarLookupEvent',
			BbcodeToHtmlStartEvent::NAME            => 'onBbcodeToHtmlEvent',
			BbcodeToMarkdownEndEvent::NAME          => 'onBbcodeToMarkdownEndEvent',
			BlockContactEvent::NAME                 => 'onBlockContactEvent',
			CacheItemEvent::NAME                    => 'onCacheItemEvent',
			CheckItemNotificationEvent::NAME        => 'onCheckItemNotificationEvent',
			ConnectorSettingsPostEvent::NAME        => 'onConnectorSettingsPostEvent',
			ContactPhotoMenuEvent::NAME             => 'onContactPhotoMenuEvent',
			ConversationStartEvent::NAME            => 'onConversationStartEvent',
			DbStructureDefinitionEvent::NAME        => 'onDbStructureDefinitionEvent',
			DbViewDefinitionEvent::NAME             => 'onDbViewDefinitionEvent',
			DetectLanguagesEvent::NAME              => 'onDetectLanguagesEvent',
			DirectoryItemEvent::NAME                => 'onDirectoryItemEvent',
			DisplayItemEvent::NAME                  => 'onDisplayItemEvent',
			DisplaySettingsPostEvent::NAME          => 'onDisplaySettingsPostEvent',
			EditContactFormEvent::NAME              => 'onEditContactFormEvent',
			EditContactPostEvent::NAME              => 'onEditContactPostEvent',
			EmailGetMessageEvent::NAME              => 'onEmailGetMessageEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE_END => 'onArrayFilterEvent',
			EmailerSendEvent::NAME                  => 'onEmailerSendEvent',
			EmailerSendPrepareEvent::NAME           => 'onEmailerSendPrepareEvent',
			EnotifyEvent::NAME                      => 'onEnotifyEvent',
			EnotifyMailEvent::NAME                  => 'onEnotifyMailEvent',
			EnotifyStoreEvent::NAME                 => 'onEnotifyStoreEvent',
			ArrayFilterEvent::EVENT_CREATED         => 'onEventCreatedEvent',
			ArrayFilterEvent::EVENT_UPDATED         => 'onEventUpdatedEvent',
			FeatureEnabledEvent::NAME               => 'onFeatureEnabledEvent',
			FeatureGetEvent::NAME                   => 'onFeatureGetEvent',
			FetchItemByLinkEvent::NAME              => 'onFetchItemByLinkEvent',
			FollowContactEvent::NAME                => 'onFollowContactEvent',
			ArrayFilterEvent::GENERATE_MAP          => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_NAMED_MAP    => 'onArrayFilterEvent',
			GetSiteInfoEvent::NAME                  => 'onGetSiteInfoEvent',
			GlobalDirUpdateEvent::NAME              => 'onGlobalDirUpdateEvent',
			HtmlToBbcodeEndEvent::NAME              => 'onHtmlToBbcodeEvent',
			InsertPostLocalEvent::NAME              => 'onInsertPostLocalEvent',
			InsertPostLocalEndEvent::NAME           => 'onInsertPostLocalEndEvent',
			InsertPostRemoteEvent::NAME             => 'onInsertPostRemoteEvent',
			InsertPostRemoteEndEvent::NAME          => 'onInsertPostRemoteEndEvent',
			InsertPostLocalStartEvent::NAME         => 'onInsertPostLocalStartEvent',
			ItemPhotoMenuEvent::NAME                => 'onItemPhotoMenuEvent',
			ItemTaggedEvent::NAME                   => 'onItemTaggedEvent',
			JotNetworksEvent::NAME                  => 'onJotNetworksEvent',
			LoggedInEvent::NAME                     => 'onLoggedInEvent',
			LoginFormEvent::NAME                    => 'onLoginFormEvent',
			MagicAuthSuccessEvent::NAME             => 'onMagicAuthSuccessEvent',
			ArrayFilterEvent::MAP_GET_COORDINATES   => 'onArrayFilterEvent',
			ModerationUsersTabsEvent::NAME          => 'onModerationUsersTabsEvent',
			NavInfoEvent::NAME                      => 'onNavInfoEvent',
			NetworkContentStartEvent::NAME          => 'onNetworkContentStartEvent',
			NetworkContentTabsEvent::NAME           => 'onNetworkContentTabsEvent',
			NetworkToNameEvent::NAME                => 'onNetworkToNameEvent',
			NotifierEndEvent::NAME                  => 'onNotifierEndEvent',
			OcrDetectionEvent::NAME                 => 'onOcrDetectionEvent',
			ArrayFilterEvent::OTHER_ENCAPSULATE     => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_UNENCAPSULATE   => 'onArrayFilterEvent',
			PageInfoEvent::NAME                     => 'onPageInfoEvent',
			ParseLinkEvent::NAME                    => 'onParseLinkEvent',
			PermissionTooltipContentEvent::NAME     => 'onPermissionTooltipContentEvent',
			PhotoUploadEvent::NAME                  => 'onPhotoUploadEvent',
			PhotoUploadEndEvent::NAME               => 'onPhotoUploadEndEvent',
			PhotoUploadFormEvent::NAME              => 'onPhotoUploadFormEvent',
			PhotoUploadStartEvent::NAME             => 'onPhotoUploadStartEvent',
			PreparePostEvent::NAME                  => 'onPreparePostEvent',
			PreparePostEndEvent::NAME               => 'onPreparePostEndEvent',
			PreparePostFilterContentEvent::NAME     => 'onPreparePostFilterContentEvent',
			PreparePostStartEvent::NAME             => 'onPreparePostStartEvent',
			ProbeDetectEvent::NAME                  => 'onProbeDetectEvent',
			ProfileSettingsFormEvent::NAME          => 'onProfileSettingsFormEvent',
			ProfileSettingsPostEvent::NAME          => 'onProfileSettingsPostEvent',
			ProfileSidebarEvent::NAME               => 'onProfileSidebarEvent',
			ProfileSidebarStartEvent::NAME          => 'onProfileSidebarStartEvent',
			ProfileTabsEvent::NAME                  => 'onProfileTabsEvent',
			ProtocolSupportsFollowEvent::NAME       => 'onProtocolSupportsFollowEvent',
			ProtocolSupportsProbeEvent::NAME        => 'onProtocolSupportsProbeEvent',
			ProtocolSupportsRevokeFollowEvent::NAME => 'onProtocolSupportsRevokeFollowEvent',
			RenderLocationEvent::NAME               => 'onRenderLocationEvent',
			RevokeFollowContactEvent::NAME          => 'onRevokeFollowContactEvent',
			SmileyListEvent::NAME                   => 'onSmileyListEvent',
			StorageConfigEvent::NAME                => 'onStorageConfigEvent',
			StorageInstanceEvent::NAME              => 'onStorageInstanceEvent',
			TemplateVarsEvent::NAME                 => 'onTemplateVarsEvent',
			UnblockContactEvent::NAME               => 'onUnblockContactEvent',
			UnfollowContactEvent::NAME              => 'onUnfollowContactEvent',
			UserExportOptionsEvent::NAME            => 'onUserExportOptionsEvent',
			ZrlInitEvent::NAME                      => 'onZrlInitEvent',
			HtmlFilterEvent::CONTACT_BLOCK_END      => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER                 => 'onHtmlFilterEvent',
			HtmlFilterEvent::HEAD                   => 'onHtmlFilterEvent',
			HtmlFilterEvent::JOT_TOOL               => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_ABOUT_CONTENT      => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_HOME_CONTENT       => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_PROFILE_CONTENT    => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP       => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END               => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_HEADER            => 'onHtmlFilterEvent',
			ModuleContentEvent::NAME                => 'onModuleContentEvent',
			ModuleInitEvent::NAME                   => 'onModuleInitEvent',
			ModulePostEvent::NAME                   => 'onModulePostEvent',
			ModulePostRecipientEvent::NAME          => 'onModulePostRecipientEvent',
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

	public function testOnGetSiteInfoEventCallsHookWithCorrectValue(): void
	{
		$event = new GetSiteInfoEvent([
			'url'  => 'https://example.org',
			'type' => 'link',
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('getsiteinfo', $name);
			$this->assertSame([
				'url'  => 'https://example.org',
				'type' => 'link',
			], $data);

			return [
				'url'   => 'https://example.org',
				'type'  => 'photo',
				'title' => 'Example',
			];
		});

		HookEventBridge::onGetSiteInfoEvent($event);

		$this->assertSame([
			'url'   => 'https://example.org',
			'type'  => 'photo',
			'title' => 'Example',
		], $event->getSiteInfoArray());
	}

	public function testOnGlobalDirUpdateEventCallsHookWithCorrectValue(): void
	{
		$event = new GlobalDirUpdateEvent('https://example.org/profile');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('globaldir_update', $name);
			$this->assertSame([
				'url' => 'https://example.org/profile',
			], $data);

			return [
				'url' => '',
			];
		});

		HookEventBridge::onGlobalDirUpdateEvent($event);

		$this->assertSame('', $event->getUrl());
	}

	public function testOnUserExportOptionsEventCallsHookWithCorrectValue(): void
	{
		$event = new UserExportOptionsEvent([
			['settings/userexport/account', 'Export account', 'Export your account info and contacts.'],
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('uexport_options', $name);
			$this->assertSame([
				['settings/userexport/account', 'Export account', 'Export your account info and contacts.'],
			], $data);

			return [
				['settings/userexport/backup', 'Export all', 'Export your account info, contacts and all your items.'],
			];
		});

		HookEventBridge::onUserExportOptionsEvent($event);

		$this->assertSame([
			['settings/userexport/backup', 'Export all', 'Export your account info, contacts and all your items.'],
		], $event->getOptionsArray());
	}

	public function testOnAppMenuEventCallsHookWithCorrectValue(): void
	{
		$event = new AppMenuEvent([]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('app_menu', $name);
			$this->assertSame(['app_menu' => []], $data);

			$data['app_menu'][] = '<div class="app-title"><a href="irc">IRC Chatroom</a></div>';

			return $data;
		});

		HookEventBridge::onAppMenuEvent($event);

		$this->assertSame(['<div class="app-title"><a href="irc">IRC Chatroom</a></div>'], $event->getAppMenuArray());
	}

	public function testOnAvatarLookupEventCallsHookWithCorrectValue(): void
	{
		$event = new AvatarLookupEvent(300, 'contact@example.com');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('avatar_lookup', $name);
			$this->assertSame([
				'size'    => 300,
				'email'   => 'contact@example.com',
				'url'     => '',
				'success' => false,
			], $data);

			return [
				'size'    => 300,
				'email'   => 'contact@example.com',
				'url'     => 'https://example.com/avatar',
				'success' => true,
			];
		});

		HookEventBridge::onAvatarLookupEvent($event);

		$this->assertSame('https://example.com/avatar', $event->getUrl());
		$this->assertTrue($event->isSuccess());
	}

	public function testOnProbeDetectEventCallsHookWithCorrectValue(): void
	{
		$event = new ProbeDetectEvent('https://example.com/profile', 'activitypub', 42);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('probe_detect', $name);
			$this->assertSame([
				'uri'     => 'https://example.com/profile',
				'network' => 'activitypub',
				'uid'     => 42,
				'result'  => null,
			], $data);

			return [
				'uri'     => 'https://example.com/profile',
				'network' => 'activitypub',
				'uid'     => 42,
				'result'  => ['name' => 'contact'],
			];
		});

		HookEventBridge::onProbeDetectEvent($event);

		$this->assertSame(['name' => 'contact'], $event->getResult());
	}

	public function testOnProtocolSupportsFollowEventCallsHookWithCorrectValue(): void
	{
		$event = new ProtocolSupportsFollowEvent('activitypub');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('support_follow', $name);
			$this->assertSame([
				'protocol' => 'activitypub',
				'result'   => null,
			], $data);

			return [
				'protocol' => 'activitypub',
				'result'   => true,
			];
		});

		HookEventBridge::onProtocolSupportsFollowEvent($event);

		$this->assertTrue($event->getResult());
	}

	public function testOnProtocolSupportsProbeEventCallsHookWithCorrectValue(): void
	{
		$event = new ProtocolSupportsProbeEvent('activitypub');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('support_probe', $name);
			$this->assertSame([
				'protocol' => 'activitypub',
				'result'   => null,
			], $data);

			return [
				'protocol' => 'activitypub',
				'result'   => true,
			];
		});

		HookEventBridge::onProtocolSupportsProbeEvent($event);

		$this->assertTrue($event->getResult());
	}

	public function testOnProtocolSupportsRevokeFollowEventCallsHookWithCorrectValue(): void
	{
		$event = new ProtocolSupportsRevokeFollowEvent('activitypub');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('support_revoke_follow', $name);
			$this->assertSame([
				'protocol' => 'activitypub',
				'result'   => null,
			], $data);

			return [
				'protocol' => 'activitypub',
				'result'   => true,
			];
		});

		HookEventBridge::onProtocolSupportsRevokeFollowEvent($event);

		$this->assertTrue($event->getResult());
	}

	public function testOnDbStructureDefinitionEventCallsHookWithCorrectValue(): void
	{
		$event = new DbStructureDefinitionEvent([
			'user' => [
				'fields' => [
					'uid' => ['type' => 'int unsigned', 'primary' => '1'],
				],
			],
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('dbstructure_definition', $name);
			$this->assertSame([
				'user' => [
					'fields' => [
						'uid' => ['type' => 'int unsigned', 'primary' => '1'],
					],
				],
			], $data);

			return [
				'rules' => [
					'fields' => [
						'id' => ['type' => 'int unsigned', 'primary' => '1'],
					],
				],
			];
		});

		HookEventBridge::onDbStructureDefinitionEvent($event);

		$this->assertSame([
			'rules' => [
				'fields' => [
					'id' => ['type' => 'int unsigned', 'primary' => '1'],
				],
			],
		], $event->getDefinitionArray());
	}

	public function testOnDbViewDefinitionEventCallsHookWithCorrectValue(): void
	{
		$event = new DbViewDefinitionEvent([
			'user-view' => [
				'fields' => [
					'uid' => ['type' => 'int unsigned'],
				],
			],
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('dbview_definition', $name);
			$this->assertSame([
				'user-view' => [
					'fields' => [
						'uid' => ['type' => 'int unsigned'],
					],
				],
			], $data);

			return [
				'post-view' => [
					'fields' => [
						'id' => ['type' => 'int unsigned'],
					],
				],
			];
		});

		HookEventBridge::onDbViewDefinitionEvent($event);

		$this->assertSame([
			'post-view' => [
				'fields' => [
					'id' => ['type' => 'int unsigned'],
				],
			],
		], $event->getDefinitionArray());
	}

	public function testOnDetectLanguagesEventCallsHookWithCorrectValue(): void
	{
		$event = new DetectLanguagesEvent('This is some text', ['en' => 0.8], 42, 99);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('detect_languages', $name);
			$this->assertSame([
				'text'      => 'This is some text',
				'detected'  => ['en' => 0.8],
				'uri-id'    => 42,
				'author-id' => 99,
			], $data);

			return [
				'text'      => 'This is some text',
				'detected'  => ['de' => 0.9],
				'uri-id'    => 42,
				'author-id' => 99,
			];
		});

		HookEventBridge::onDetectLanguagesEvent($event);

		$this->assertSame(['de' => 0.9], $event->getDetected());
	}

	public function testOnFeatureEnabledEventCallsHookWithCorrectValue(): void
	{
		$event = new FeatureEnabledEvent(42, 'expanding_events', false);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('isEnabled', $name);
			$this->assertSame([
				'uid'     => 42,
				'feature' => 'expanding_events',
				'enabled' => false,
			], $data);

			return [
				'uid'     => 42,
				'feature' => 'expanding_events',
				'enabled' => true,
			];
		});

		HookEventBridge::onFeatureEnabledEvent($event);

		$this->assertTrue($event->isEnabled());
	}

	public function testOnFeatureGetEventCallsHookWithCorrectValue(): void
	{
		$features = ['general' => ['General Settings', [['expanding', 'Expanding Events', 'Provide the ability to expand posts', true, false]]]];
		$event    = new FeatureGetEvent($features);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('get', $name);
			$this->assertSame(['general' => ['General Settings', [['expanding', 'Expanding Events', 'Provide the ability to expand posts', true, false]]]], $data);

			return ['network' => ['Network Widgets', [['circles', 'Circles', 'Display posts of the selected circle', true, false]]]];
		});

		HookEventBridge::onFeatureGetEvent($event);

		$this->assertSame(['network' => ['Network Widgets', [['circles', 'Circles', 'Display posts of the selected circle', true, false]]]], $event->getFeatures());
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
		$event = new PermissionTooltipContentEvent(['uid' => -1]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('lockview_content', $name);
			$this->assertSame(['uid' => -1], $data);

			return ['uid' => 123];
		});

		HookEventBridge::onPermissionTooltipContentEvent($event);

		$this->assertSame(['uid' => 123], $event->getModelArray());
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

	public function testOnStorageConfigEventCallsHookWithCorrectValue(): void
	{
		$event     = new StorageConfigEvent('s3_storage');
		$getConfig = $this->createStub(ICanConfigureStorage::class);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data) use ($getConfig): array {
			$this->assertSame('storage_config', $name);
			$this->assertSame([
				'name'           => 's3_storage',
				'storage_config' => null,
			], $data);

			return [
				'name'           => 's3_storage',
				'storage_config' => $getConfig,
			];
		});

		HookEventBridge::onStorageConfigEvent($event);

		$this->assertSame($getConfig, $event->getConfig());
	}

	public function testOnStorageConfigEventKeepsConfigNullOnEmptyHookData(): void
	{
		$event = new StorageConfigEvent('s3_storage');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			return [];
		});

		HookEventBridge::onStorageConfigEvent($event);

		$this->assertNull($event->getConfig());
	}

	public function testOnStorageInstanceEventCallsHookWithCorrectValue(): void
	{
		$event   = new StorageInstanceEvent('s3_storage');
		$storage = $this->createStub(ICanReadFromStorage::class);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data) use ($storage): array {
			$this->assertSame('storage_instance', $name);
			$this->assertSame([
				'name'    => 's3_storage',
				'storage' => null,
			], $data);

			return [
				'name'    => 's3_storage',
				'storage' => $storage,
			];
		});

		HookEventBridge::onStorageInstanceEvent($event);

		$this->assertSame($storage, $event->getStorage());
	}

	public function testOnStorageInstanceEventKeepsStorageNullOnEmptyHookData(): void
	{
		$event = new StorageInstanceEvent('s3_storage');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			return [];
		});

		HookEventBridge::onStorageInstanceEvent($event);

		$this->assertNull($event->getStorage());
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

	public function testOnAclLookupEndEventCallsHookWithCorrectValue(): void
	{
		$event = new AclLookupEndEvent(
			5,
			0,
			10,
			[['type' => 'circle', 'name' => 'Friends', 'id' => 1]],
			[['type' => 'contact', 'name' => 'John', 'id' => 2]],
			[['type' => 'contact', 'name' => 'Jane', 'id' => 3]],
			'contact',
			'john',
		);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('acl_lookup_end', $name);
			$this->assertSame([
				'tot'      => 5,
				'start'    => 0,
				'count'    => 10,
				'circles'  => [['type' => 'circle', 'name' => 'Friends', 'id' => 1]],
				'contacts' => [['type' => 'contact', 'name' => 'John', 'id' => 2]],
				'items'    => [['type' => 'contact', 'name' => 'Jane', 'id' => 3]],
				'type'     => 'contact',
				'search'   => 'john',
			], $data);

			return [
				'tot'      => 6,
				'start'    => 1,
				'count'    => 20,
				'circles'  => [['type' => 'circle', 'name' => 'Friends', 'id' => 1]],
				'contacts' => [['type' => 'contact', 'name' => 'John', 'id' => 2]],
				'items'    => [['type' => 'contact', 'name' => 'Joe', 'id' => 4]],
				'type'     => 'contact',
				'search'   => 'john',
			];
		});

		HookEventBridge::onAclLookupEndEvent($event);

		$this->assertSame(6, $event->getTotal());
		$this->assertSame(1, $event->getStart());
		$this->assertSame(20, $event->getCount());
		$this->assertSame([['type' => 'contact', 'name' => 'Joe', 'id' => 4]], $event->getItems());
	}

	public function testOnAddWorkerTaskEventCallsHookWithCorrectValue(): void
	{
		$event = new AddWorkerTaskEvent([Worker::PRIORITY_MEDIUM, 'Notifier', 123], true);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('proc_run', $name);
			$this->assertSame([
				'args'    => [Worker::PRIORITY_MEDIUM, 'Notifier', 123],
				'run_cmd' => true,
			], $data);

			$data['run_cmd'] = false;

			return $data;
		});

		HookEventBridge::onAddWorkerTaskEvent($event);

		$this->assertFalse($event->isRunCmd());
	}

	public function testOnAddonSettingsPostEventCallsHookWithCorrectValue(): void
	{
		$event = new AddonSettingsPostEvent([
			'irc-submit' => 'foo',
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('addon_settings_post', $name);
			$this->assertSame([
				'irc-submit' => 'foo',
			], $data);

			return $data;
		});

		HookEventBridge::onAddonSettingsPostEvent($event);
	}

	public function testOnConnectorSettingsPostEventCallsHookWithCorrectValue(): void
	{
		$event = new ConnectorSettingsPostEvent([
			'diaspora-submit' => 'foo',
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('connector_settings_post', $name);
			$this->assertSame([
				'diaspora-submit' => 'foo',
			], $data);

			return $data;
		});

		HookEventBridge::onConnectorSettingsPostEvent($event);
	}

	public function testOnDisplaySettingsPostEventCallsHookWithCorrectValue(): void
	{
		$event = new DisplaySettingsPostEvent([
			'theme' => 'frio',
		]);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('display_settings_post', $name);
			$this->assertSame([
				'theme' => 'frio',
			], $data);

			return $data;
		});

		HookEventBridge::onDisplaySettingsPostEvent($event);
	}

	public function testOnEmailerSendEventCallsHookWithCorrectValue(): void
	{
		$event = new EmailerSendEvent('recipient@example.com', 'Subject', 'Body', 'Header', '-f sender@example.com');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('emailer_send', $name);
			$this->assertSame([
				'to'         => 'recipient@example.com',
				'subject'    => 'Subject',
				'body'       => 'Body',
				'headers'    => 'Header',
				'parameters' => '-f sender@example.com',
				'sent'       => false,
			], $data);

			$data['sent'] = true;

			return $data;
		});

		HookEventBridge::onEmailerSendEvent($event);

		$this->assertTrue($event->isSent());
	}

	public function testOnEmailerSendEventCallsHookWithNullParameters(): void
	{
		$event = new EmailerSendEvent('recipient@example.com', 'Subject', 'Body', 'Header', null);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertNull($data['parameters']);

			return $data;
		});

		HookEventBridge::onEmailerSendEvent($event);

		$this->assertFalse($event->isSent());
	}

	public function testOnEmailerSendPrepareEventCallsHookWithCorrectValue(): void
	{
		$email = \Mockery::mock(IEmail::class);
		$event = new EmailerSendPrepareEvent($email);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, IEmail $data) use ($email): IEmail {
			$this->assertSame('emailer_send_prepare', $name);
			$this->assertSame($email, $data);

			return $data;
		});

		HookEventBridge::onEmailerSendPrepareEvent($event);

		$this->assertSame($email, $event->getEmail());
	}

	public function testOnEmailerSendPrepareEventCallsHookWithNullValue(): void
	{
		$event = new EmailerSendPrepareEvent();

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, ?IEmail $data): ?IEmail {
			$this->assertSame('emailer_send_prepare', $name);
			$this->assertNull($data);

			return $data;
		});

		HookEventBridge::onEmailerSendPrepareEvent($event);

		$this->assertNull($event->getEmail());
	}

	public function testOnEmailerSendPrepareEventCallsHookWithWrongValue(): void
	{
		$event = new EmailerSendPrepareEvent();

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, ?IEmail $data) {
			$this->assertSame('emailer_send_prepare', $name);
			$this->assertNull($data);

			return 'wrong type';
		});

		HookEventBridge::onEmailerSendPrepareEvent($event);

		$this->assertNull($event->getEmail());
	}

	public function testOnEmailGetMessageEventCallsHookWithCorrectValue(): void
	{
		$event = new EmailGetMessageEvent('Text', 'Html', ['body' => 'Body']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('email_getmessage', $name);
			$this->assertSame([
				'text' => 'Text',
				'html' => 'Html',
				'item' => ['body' => 'Body'],
			], $data);

			$data['text'] = 'New Text';
			$data['html'] = 'New Html';
			$data['item'] = ['body' => 'New Body'];

			return $data;
		});

		HookEventBridge::onEmailGetMessageEvent($event);

		$this->assertSame('New Text', $event->getText());
		$this->assertSame('New Html', $event->getHtml());
		$this->assertSame(['body' => 'New Body'], $event->getItemArray());
	}

	public function testOnEmailGetMessageEventCallsHookWithMissingValues(): void
	{
		$event = new EmailGetMessageEvent('Text', 'Html', ['body' => 'Body']);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('email_getmessage', $name);

			return [];
		});

		HookEventBridge::onEmailGetMessageEvent($event);

		$this->assertSame('Text', $event->getText());
		$this->assertSame('Html', $event->getHtml());
		$this->assertSame(['body' => 'Body'], $event->getItemArray());
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

	public function testOnModerationUsersTabsEventCallsHookWithCorrectValue(): void
	{
		$tabs = [
			[
				'label'     => 'Users',
				'url'       => 'moderation/users',
				'sel'       => 'active',
				'title'     => 'List of users',
				'id'        => 'admin-users',
				'accesskey' => 'u',
			],
		];
		$event = new ModerationUsersTabsEvent($tabs, 'users');

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('moderation_users_tabs', $name);
			$this->assertSame([
				[
					'label'     => 'Users',
					'url'       => 'moderation/users',
					'sel'       => 'active',
					'title'     => 'List of users',
					'id'        => 'admin-users',
					'accesskey' => 'u',
				],
			], $data['tabs']);
			$this->assertSame('users', $data['selectedTab']);

			$data['tabs'][] = [
				'label'     => 'Behaviour',
				'url'       => 'ratioed',
				'sel'       => '',
				'title'     => 'Statistics about users behaviour',
				'id'        => 'admin-users-ratioed',
				'accesskey' => 'r',
			];

			return $data;
		});

		HookEventBridge::onModerationUsersTabsEvent($event);

		$this->assertCount(2, $event->getTabsArray());
		$this->assertSame('Behaviour', $event->getTabsArray()[1]['label']);
	}

	public function testOnNavInfoEventCallsHookWithCorrectValue(): void
	{
		$event = new NavInfoEvent(
			'<span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>',
			['login' => ['login', 'Sign in', 'selected', 'Sign in']],
			'@friendica@friendi.ca',
			null,
		);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			$this->assertSame('nav_info', $name);
			$this->assertSame([
				'banner'       => '<span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>',
				'nav'          => ['login' => ['login', 'Sign in', 'selected', 'Sign in']],
				'sitelocation' => '@friendica@friendi.ca',
				'userinfo'     => null,
			], $data);

			return [
				'banner'       => '<a href="https://friendi.ca">Friendica</a>',
				'nav'          => ['logout' => ['logout', 'Sign out', '', 'End this session']],
				'sitelocation' => '@friendica@friendi.ca',
				'userinfo'     => ['icon' => 'images/user.png', 'name' => 'John', 'link' => 'profile/john'],
			];
		});

		HookEventBridge::onNavInfoEvent($event);

		$this->assertSame('<a href="https://friendi.ca">Friendica</a>', $event->getBanner());
		$this->assertSame(['logout' => ['logout', 'Sign out', '', 'End this session']], $event->getNavArray());
		$this->assertSame('@friendica@friendi.ca', $event->getSitelocation());
		$this->assertSame(['icon' => 'images/user.png', 'name' => 'John', 'link' => 'profile/john'], $event->getUserinfoArray());
	}

	public function testOnNavInfoEventKeepsUserinfoNullOnEmptyHookData(): void
	{
		$event = new NavInfoEvent('<h1>Banner</h1>', ['network' => null], '@friendica@friendi.ca', null);

		$reflectionProperty = new \ReflectionProperty(HookEventBridge::class, 'mockedCallHook');

		$reflectionProperty->setValue(null, function (string $name, array $data): array {
			return [];
		});

		HookEventBridge::onNavInfoEvent($event);

		$this->assertNull($event->getUserinfoArray());
	}

	public static function getArrayFilterEventData(): array
	{
		return [
			['test', 'test'],
			[AppMenuEvent::NAME, 'app_menu'],
			[NavInfoEvent::NAME, 'nav_info'],
			[PhotoUploadEvent::NAME, 'photo_post_file'],
			[NetworkToNameEvent::NAME, 'network_to_name'],
			[NetworkContentStartEvent::NAME, 'network_content_init'],
			[NetworkContentTabsEvent::NAME, 'network_tabs'],
			[ParseLinkEvent::NAME, 'parse_link'],
			[EnotifyEvent::NAME, 'enotify'],
			[EnotifyMailEvent::NAME, 'enotify_mail'],
			[EnotifyStoreEvent::NAME, 'enotify_store'],
			[RenderLocationEvent::NAME, 'render_location'],
			[ContactPhotoMenuEvent::NAME, 'contact_photo_menu'],
			[ProfileSettingsFormEvent::NAME, 'profile_edit'],
			[ProfileSettingsPostEvent::NAME, 'profile_post'],
			[ModerationUsersTabsEvent::NAME, 'moderation_users_tabs'],
			[AclLookupEndEvent::NAME, 'acl_lookup_end'],
			[PageInfoEvent::NAME, 'page_info_data'],
			[SmileyListEvent::NAME, 'smilie'],
			[JotNetworksEvent::NAME, 'jot_networks'],
			[FollowContactEvent::NAME, 'follow'],
			[GetSiteInfoEvent::NAME, 'getsiteinfo'],
			[GlobalDirUpdateEvent::NAME, 'globaldir_update'],
			[UnfollowContactEvent::NAME, 'unfollow'],
			[UserExportOptionsEvent::NAME, 'uexport_options'],
			[RevokeFollowContactEvent::NAME, 'revoke_follow'],
			[BlockContactEvent::NAME, 'block'],
			[UnblockContactEvent::NAME, 'unblock'],
			[EditContactFormEvent::NAME, 'contact_edit'],
			[EditContactPostEvent::NAME, 'contact_edit_post'],
			[AccountAuthenticateEvent::NAME, 'authenticate'],
			[AccountRegisterFormEvent::NAME, 'register_form'],
			[AccountRegisterPostEvent::NAME, 'register_post'],
			[AccountRegisterEvent::NAME, 'register_account'],
			[ArrayFilterEvent::EVENT_CREATED, 'event_created'],
			[ArrayFilterEvent::EVENT_UPDATED, 'event_updated'],
			[AddWorkerTaskEvent::NAME, 'proc_run'],
			[AddonSettingsPostEvent::NAME, 'addon_settings_post'],
			[ConnectorSettingsPostEvent::NAME, 'connector_settings_post'],
			[DisplaySettingsPostEvent::NAME, 'display_settings_post'],
			[StorageConfigEvent::NAME, 'storage_config'],
			[StorageInstanceEvent::NAME, 'storage_instance'],
			[DbStructureDefinitionEvent::NAME, 'dbstructure_definition'],
			[DbViewDefinitionEvent::NAME, 'dbview_definition'],
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
