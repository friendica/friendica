<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Hooks;

use Friendica\Core\Hook;
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
use Friendica\Event\ContactPhotoMenuEvent;
use Friendica\Event\ConnectorSettingsPostEvent;
use Friendica\Event\ConversationStartEvent;
use Friendica\Event\DbStructureDefinitionEvent;
use Friendica\Event\DbViewDefinitionEvent;
use Friendica\Event\DetectLanguagesEvent;
use Friendica\Event\DirectoryItemEvent;
use Friendica\Event\DisplayItemEvent;
use Friendica\Event\DisplaySettingsPostEvent;
use Friendica\Event\EmailGetMessageEvent;
use Friendica\Event\EmailGetMessageEndEvent;
use Friendica\Event\EmailerSendEvent;
use Friendica\Event\EmailerSendPrepareEvent;
use Friendica\Event\EnotifyEvent;
use Friendica\Event\EnotifyMailEvent;
use Friendica\Event\EnotifyStoreEvent;
use Friendica\Event\EditContactFormEvent;
use Friendica\Event\EditContactPostEvent;
use Friendica\Event\EventCreatedEvent;
use Friendica\Event\EventUpdatedEvent;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Event\FeatureEnabledEvent;
use Friendica\Event\FeatureGetEvent;
use Friendica\Event\FollowContactEvent;
use Friendica\Event\FooterEvent;
use Friendica\Event\GenerateMapEvent;
use Friendica\Event\GenerateNamedMapEvent;
use Friendica\Event\GetSiteInfoEvent;
use Friendica\Event\GlobalDirUpdateEvent;
use Friendica\Event\HeadEvent;
use Friendica\Event\HtmlToBbcodeEndEvent;
use Friendica\Event\InsertPostLocalEvent;
use Friendica\Event\InsertPostLocalStartEvent;
use Friendica\Event\InsertPostLocalEndEvent;
use Friendica\Event\InsertPostRemoteEvent;
use Friendica\Event\InsertPostRemoteEndEvent;
use Friendica\Event\JotNetworksEvent;
use Friendica\Event\ItemPhotoMenuEvent;
use Friendica\Event\ItemTaggedEvent;
use Friendica\Event\PreparePostEndEvent;
use Friendica\Event\PreparePostEvent;
use Friendica\Event\PreparePostFilterContentEvent;
use Friendica\Event\PreparePostStartEvent;
use Friendica\Event\PhotoUploadEndEvent;
use Friendica\Event\PhotoUploadEvent;
use Friendica\Event\PhotoUploadFormEvent;
use Friendica\Event\PhotoUploadStartEvent;
use Friendica\Event\ProbeDetectEvent;
use Friendica\Event\ProfileSettingsFormEvent;
use Friendica\Event\ProfileSettingsPostEvent;
use Friendica\Event\ProfileSidebarEvent;
use Friendica\Event\ProfileSidebarStartEvent;
use Friendica\Event\ProfileTabsEvent;
use Friendica\Event\ProtocolSupportsFollowEvent;
use Friendica\Event\ProtocolSupportsProbeEvent;
use Friendica\Event\ProtocolSupportsRevokeFollowEvent;
use Friendica\Event\CollectRoutesEvent;
use Friendica\Event\LoggedInEvent;
use Friendica\Event\LoginFormEvent;
use Friendica\Event\MagicAuthSuccessEvent;
use Friendica\Event\MapGetCoordinatesEvent;
use Friendica\Event\ModerationUsersTabsEvent;
use Friendica\Event\NavInfoEvent;
use Friendica\Event\NetworkToNameEvent;
use Friendica\Event\NetworkContentStartEvent;
use Friendica\Event\NetworkContentTabsEvent;
use Friendica\Event\ZrlInitEvent;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Event\HomeInitEvent;
use Friendica\Event\InitEvent;
use Friendica\Event\LoggingOutEvent;
use Friendica\Event\NotifierEndEvent;
use Friendica\Event\OcrDetectionEvent;
use Friendica\Event\OtherEncapsulateEvent;
use Friendica\Event\OtherUnencapsulateEvent;
use Friendica\Event\PageHeaderEvent;
use Friendica\Event\PageInfoEvent;
use Friendica\Event\ParseLinkEvent;
use Friendica\Object\EMail\IEmail;
use Friendica\Event\PermissionTooltipContentEvent;
use Friendica\Event\RenderLocationEvent;
use Friendica\Event\RevokeFollowContactEvent;
use Friendica\Event\SmileyListEvent;
use Friendica\Event\StorageConfigEvent;
use Friendica\Event\StorageInstanceEvent;
use Friendica\Event\TemplateVarsEvent;
use Friendica\Event\UnblockContactEvent;
use Friendica\Event\UnfollowContactEvent;
use Friendica\Event\UserExportOptionsEvent;
use Friendica\Event\ModuleContentEvent;
use Friendica\Event\ModuleInitEvent;
use Friendica\Event\ModulePostEvent;
use Friendica\Event\ModulePostRecipientEvent;
use Friendica\Core\Event\NamedEvent;

/**
 * Bridge between the EventDispatcher and the Hook class.
 *
 * @internal Provides BC
 */
final class HookEventBridge
{
	/** @phpstan-ignore property.unusedType(This allows us to mock the Hook call in tests.) */
	private static ?\Closure $mockedCallHook = null;

	/**
	 * This maps the new event names to the legacy Hook names.
	 */
	private static array $eventMapper = [
		InitEvent::NAME                         => 'init_1',
		HomeInitEvent::NAME                     => 'home_init',
		LoggingOutEvent::NAME                   => 'logging_out',
		ConfigLoadedEvent::NAME                 => 'load_config',
		CollectRoutesEvent::NAME                => 'route_collection',
		AccountAuthenticateEvent::NAME          => 'authenticate',
		AccountRegisterEvent::NAME              => 'register_account',
		AccountRegisterFormEvent::NAME          => 'register_form',
		AccountRegisterPostEvent::NAME          => 'register_post',
		AccountRemoveEvent::NAME                => 'remove_user',
		AclLookupEndEvent::NAME                 => 'acl_lookup_end',
		AddWorkerTaskEvent::NAME                => 'proc_run',
		AddonSettingsPostEvent::NAME            => 'addon_settings_post',
		AppMenuEvent::NAME                      => 'app_menu',
		AvatarLookupEvent::NAME                 => 'avatar_lookup',
		BbcodeToHtmlStartEvent::NAME            => 'bbcode',
		BbcodeToMarkdownEndEvent::NAME          => 'bb2diaspora',
		BlockContactEvent::NAME                 => 'block',
		CacheItemEvent::NAME                    => 'put_item_in_cache',
		CheckItemNotificationEvent::NAME        => 'check_item_notification',
		ConnectorSettingsPostEvent::NAME        => 'connector_settings_post',
		ContactPhotoMenuEvent::NAME             => 'contact_photo_menu',
		ConversationStartEvent::NAME            => 'conversation_start',
		DbStructureDefinitionEvent::NAME        => 'dbstructure_definition',
		DbViewDefinitionEvent::NAME             => 'dbview_definition',
		DetectLanguagesEvent::NAME              => 'detect_languages',
		DirectoryItemEvent::NAME                => 'directory_item',
		DisplayItemEvent::NAME                  => 'display_item',
		DisplaySettingsPostEvent::NAME          => 'display_settings_post',
		EditContactFormEvent::NAME              => 'contact_edit',
		EditContactPostEvent::NAME              => 'contact_edit_post',
		EmailGetMessageEvent::NAME              => 'email_getmessage',
		EmailGetMessageEndEvent::NAME           => 'email_getmessage_end',
		EmailerSendEvent::NAME                  => 'emailer_send',
		EmailerSendPrepareEvent::NAME           => 'emailer_send_prepare',
		EnotifyEvent::NAME                      => 'enotify',
		EnotifyMailEvent::NAME                  => 'enotify_mail',
		EnotifyStoreEvent::NAME                 => 'enotify_store',
		EventCreatedEvent::NAME                 => 'event_created',
		EventUpdatedEvent::NAME                 => 'event_updated',
		FeatureEnabledEvent::NAME               => 'isEnabled',
		FeatureGetEvent::NAME                   => 'get',
		FetchItemByLinkEvent::NAME              => 'item_by_link',
		FollowContactEvent::NAME                => 'follow',
		GenerateMapEvent::NAME                  => 'generate_map',
		GenerateNamedMapEvent::NAME             => 'generate_named_map',
		GetSiteInfoEvent::NAME                  => 'getsiteinfo',
		GlobalDirUpdateEvent::NAME              => 'globaldir_update',
		HtmlToBbcodeEndEvent::NAME              => 'html2bbcode',
		InsertPostLocalEvent::NAME              => 'post_local',
		InsertPostLocalEndEvent::NAME           => 'post_local_end',
		InsertPostRemoteEvent::NAME             => 'post_remote',
		InsertPostRemoteEndEvent::NAME          => 'post_remote_end',
		InsertPostLocalStartEvent::NAME         => 'post_local_start',
		ItemPhotoMenuEvent::NAME                => 'item_photo_menu',
		ItemTaggedEvent::NAME                   => 'tagged',
		JotNetworksEvent::NAME                  => 'jot_networks',
		LoggedInEvent::NAME                     => 'logged_in',
		LoginFormEvent::NAME                    => 'login_hook',
		MagicAuthSuccessEvent::NAME             => 'magic_auth_success',
		MapGetCoordinatesEvent::NAME            => 'Map::getCoordinates',
		ModerationUsersTabsEvent::NAME          => 'moderation_users_tabs',
		NavInfoEvent::NAME                      => 'nav_info',
		NetworkContentStartEvent::NAME          => 'network_content_init',
		NetworkContentTabsEvent::NAME           => 'network_tabs',
		NetworkToNameEvent::NAME                => 'network_to_name',
		NotifierEndEvent::NAME                  => 'notifier_end',
		OcrDetectionEvent::NAME                 => 'ocr-detection',
		OtherEncapsulateEvent::NAME             => 'other_encapsulate',
		OtherUnencapsulateEvent::NAME           => 'other_unencapsulate',
		PageInfoEvent::NAME                     => 'page_info_data',
		ParseLinkEvent::NAME                    => 'parse_link',
		PermissionTooltipContentEvent::NAME     => 'lockview_content',
		PhotoUploadEvent::NAME                  => 'photo_post_file',
		PhotoUploadEndEvent::NAME               => 'photo_post_end',
		PhotoUploadFormEvent::NAME              => 'photo_upload_form',
		PhotoUploadStartEvent::NAME             => 'photo_post_init',
		PreparePostEvent::NAME                  => 'prepare_body',
		PreparePostEndEvent::NAME               => 'prepare_body_final',
		PreparePostFilterContentEvent::NAME     => 'prepare_body_content_filter',
		PreparePostStartEvent::NAME             => 'prepare_body_init',
		ProbeDetectEvent::NAME                  => 'probe_detect',
		ProfileSettingsFormEvent::NAME          => 'profile_edit',
		ProfileSettingsPostEvent::NAME          => 'profile_post',
		ProfileSidebarEvent::NAME               => 'profile_sidebar',
		ProfileSidebarStartEvent::NAME          => 'profile_sidebar_enter',
		ProfileTabsEvent::NAME                  => 'profile_tabs',
		ProtocolSupportsFollowEvent::NAME       => 'support_follow',
		ProtocolSupportsProbeEvent::NAME        => 'support_probe',
		ProtocolSupportsRevokeFollowEvent::NAME => 'support_revoke_follow',
		RenderLocationEvent::NAME               => 'render_location',
		RevokeFollowContactEvent::NAME          => 'revoke_follow',
		SmileyListEvent::NAME                   => 'smilie',
		StorageConfigEvent::NAME                => 'storage_config',
		StorageInstanceEvent::NAME              => 'storage_instance',
		TemplateVarsEvent::NAME                 => 'template_vars',
		UnblockContactEvent::NAME               => 'unblock',
		UnfollowContactEvent::NAME              => 'unfollow',
		UserExportOptionsEvent::NAME            => 'uexport_options',
		ZrlInitEvent::NAME                      => 'zrl_init',
		HeadEvent::NAME                         => 'head',
		FooterEvent::NAME                       => 'footer',
		PageHeaderEvent::NAME                   => 'page_header',
		HtmlFilterEvent::PAGE_CONTENT_TOP       => 'page_content_top',
		HtmlFilterEvent::PAGE_END               => 'page_end',
		HtmlFilterEvent::MOD_HOME_CONTENT       => 'home_content',
		HtmlFilterEvent::MOD_ABOUT_CONTENT      => 'about_hook',
		HtmlFilterEvent::MOD_PROFILE_CONTENT    => 'profile_advanced',
		HtmlFilterEvent::JOT_TOOL               => 'jot_tool',
		HtmlFilterEvent::CONTACT_BLOCK_END      => 'contact_block_end',
	];

	/**
	 * @return array<string, string>
	 */
	public static function getStaticSubscribedEvents(): array
	{
		return [
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
			EmailGetMessageEndEvent::NAME           => 'onEmailGetMessageEndEvent',
			EmailerSendEvent::NAME                  => 'onEmailerSendEvent',
			EmailerSendPrepareEvent::NAME           => 'onEmailerSendPrepareEvent',
			EnotifyEvent::NAME                      => 'onEnotifyEvent',
			EnotifyMailEvent::NAME                  => 'onEnotifyMailEvent',
			EnotifyStoreEvent::NAME                 => 'onEnotifyStoreEvent',
			EventCreatedEvent::NAME                 => 'onEventCreatedEvent',
			EventUpdatedEvent::NAME                 => 'onEventUpdatedEvent',
			FeatureEnabledEvent::NAME               => 'onFeatureEnabledEvent',
			FeatureGetEvent::NAME                   => 'onFeatureGetEvent',
			FetchItemByLinkEvent::NAME              => 'onFetchItemByLinkEvent',
			FollowContactEvent::NAME                => 'onFollowContactEvent',
			GenerateMapEvent::NAME                  => 'onGenerateMapEvent',
			GenerateNamedMapEvent::NAME             => 'onGenerateNamedMapEvent',
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
			MapGetCoordinatesEvent::NAME            => 'onMapGetCoordinatesEvent',
			ModerationUsersTabsEvent::NAME          => 'onModerationUsersTabsEvent',
			NavInfoEvent::NAME                      => 'onNavInfoEvent',
			NetworkContentStartEvent::NAME          => 'onNetworkContentStartEvent',
			NetworkContentTabsEvent::NAME           => 'onNetworkContentTabsEvent',
			NetworkToNameEvent::NAME                => 'onNetworkToNameEvent',
			NotifierEndEvent::NAME                  => 'onNotifierEndEvent',
			OcrDetectionEvent::NAME                 => 'onOcrDetectionEvent',
			OtherEncapsulateEvent::NAME             => 'onOtherEncapsulateEvent',
			OtherUnencapsulateEvent::NAME           => 'onOtherUnencapsulateEvent',
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
			FooterEvent::NAME                       => 'onFooterEvent',
			HeadEvent::NAME                         => 'onHeadEvent',
			HtmlFilterEvent::JOT_TOOL               => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_ABOUT_CONTENT      => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_HOME_CONTENT       => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_PROFILE_CONTENT    => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP       => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END               => 'onHtmlFilterEvent',
			PageHeaderEvent::NAME                   => 'onPageHeaderEvent',
			ModuleContentEvent::NAME                => 'onModuleContentEvent',
			ModuleInitEvent::NAME                   => 'onModuleInitEvent',
			ModulePostEvent::NAME                   => 'onModulePostEvent',
			ModulePostRecipientEvent::NAME          => 'onModulePostRecipientEvent',
		];
	}

	public static function onNamedEvent(NamedEvent $event): void
	{
		static::callHook($event->getName(), '');
	}

	/**
	 * Map the DisplayItemEvent to `display_item` hook
	 */
	public static function onDisplayItemEvent(DisplayItemEvent $event): void
	{
		$hook_data = [
			'item'   => $event->getItemArray(),
			'output' => $event->getTemplateDataArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setTemplateDataArray($hook_data['output'] ?? []);
	}

	/**
	 * Map the DisplaySettingsPostEvent to `display_settings_post` hook
	 */
	public static function onDisplaySettingsPostEvent(DisplaySettingsPostEvent $event): void
	{
		static::callHook($event->getName(), $event->getRequestArray());
	}

	/**
	 * Map the CacheItemEvent to `put_item_in_cache` hook
	 */
	public static function onCacheItemEvent(CacheItemEvent $event): void
	{
		$hook_data = [
			'item'          => $event->getItemArray(),
			'rendered-html' => $event->getRenderedHtml(),
			'rendered-hash' => $event->getRenderedHash(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setRenderedHtml($hook_data['rendered-html'] ?? '');
		$event->setRenderedHash($hook_data['rendered-hash'] ?? '');
	}

	/**
	 * Map the CheckItemNotificationEvent to `check_item_notification` hook
	 */
	public static function onCheckItemNotificationEvent(CheckItemNotificationEvent $event): void
	{
		$hook_data = [
			'uid'      => $event->getUserId(),
			'profiles' => $event->getProfilesArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setProfilesArray($hook_data['profiles'] ?? []);
	}

	/**
	 * Map the ConnectorSettingsPostEvent to `connector_settings_post` hook
	 */
	public static function onConnectorSettingsPostEvent(ConnectorSettingsPostEvent $event): void
	{
		static::callHook($event->getName(), $event->getRequestArray());
	}

	/**
	 * Map the ConversationStartEvent to `conversation_start` hook
	 */
	public static function onConversationStartEvent(ConversationStartEvent $event): void
	{
		$hook_data = [
			'items'   => $event->getItemsArray(),
			'mode'    => $event->getMode(),
			'update'  => $event->isUpdate(),
			'preview' => $event->isPreview(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setItemsArray($hook_data['items'] ?? []);
	}

	/**
	 * Map the DbStructureDefinitionEvent to `dbstructure_definition` hook
	 */
	public static function onDbStructureDefinitionEvent(DbStructureDefinitionEvent $event): void
	{
		$event->setDefinitionArray(
			static::callHook($event->getName(), $event->getDefinitionArray()),
		);
	}

	/**
	 * Map the DbViewDefinitionEvent to `dbview_definition` hook
	 */
	public static function onDbViewDefinitionEvent(DbViewDefinitionEvent $event): void
	{
		$event->setDefinitionArray(
			static::callHook($event->getName(), $event->getDefinitionArray()),
		);
	}

	/**
	 * Map the DetectLanguagesEvent to `detect_languages` hook
	 */
	public static function onDetectLanguagesEvent(DetectLanguagesEvent $event): void
	{
		$hook_data = [
			'text'      => $event->getText(),
			'detected'  => $event->getDetected(),
			'uri-id'    => $event->getUriId(),
			'author-id' => $event->getAuthorId(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setDetected((array) $hook_data['detected']);
	}

	/**
	 * Map the FetchItemByLinkEvent to `item_by_link` hook
	 */
	public static function onFetchItemByLinkEvent(FetchItemByLinkEvent $event): void
	{
		$hook_data = [
			'uri'     => $event->getUri(),
			'uid'     => $event->getUserId(),
			'item_id' => $event->getItemId(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setItemId(isset($hook_data['item_id']) ? (int) $hook_data['item_id'] : null);
	}

	/**
	 * Map the FeatureEnabledEvent to `isEnabled` hook
	 */
	public static function onFeatureEnabledEvent(FeatureEnabledEvent $event): void
	{
		$hook_data = [
			'uid'     => $event->getUid(),
			'feature' => $event->getFeature(),
			'enabled' => $event->isEnabled(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setEnabled((bool) $hook_data['enabled']);
	}

	/**
	 * Map the FeatureGetEvent to `get` hook
	 */
	public static function onFeatureGetEvent(FeatureGetEvent $event): void
	{
		$event->setFeatures(
			(array) static::callHook($event->getName(), $event->getFeatures()),
		);
	}

	/**
	 * Map the FollowContactEvent to `follow` hook
	 */
	public static function onFollowContactEvent(FollowContactEvent $event): void
	{
		$hook_data = [
			'url'     => $event->getUrl(),
			'uid'     => $event->getUid(),
			'contact' => $event->getContactArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (empty($hook_data)) {
			$event->setAborted();
			return;
		}

		$event->setContactArray($hook_data['contact'] ?? $event->getContactArray());
	}

	/**
	 * Map the GetSiteInfoEvent to `getsiteinfo` hook
	 */
	public static function onGetSiteInfoEvent(GetSiteInfoEvent $event): void
	{
		$event->setSiteInfoArray(
			static::callHook($event->getName(), $event->getSiteInfoArray()),
		);
	}

	/**
	 * Map the GlobalDirUpdateEvent to `globaldir_update` hook
	 */
	public static function onGlobalDirUpdateEvent(GlobalDirUpdateEvent $event): void
	{
		$hook_data = [
			'url' => $event->getUrl(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setUrl((string) ($hook_data['url'] ?? $event->getUrl()));
	}

	/**
	 * Map the ItemTaggedEvent to `tagged` hook
	 */
	public static function onItemTaggedEvent(ItemTaggedEvent $event): void
	{
		$hook_data = [
			'item' => $event->getItemArray(),
			'user' => $event->getUserArray(),
		];

		static::callHook($event->getName(), $hook_data);
	}

	/**
	 * Map the ItemPhotoMenuEvent to `item_photo_menu` hook
	 */
	public static function onItemPhotoMenuEvent(ItemPhotoMenuEvent $event): void
	{
		$hook_data = [
			'item' => $event->getItemArray(),
			'menu' => $event->getMenuArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setMenuArray($hook_data['menu'] ?? []);
	}

	/**
	 * Map the DirectoryItemEvent to `directory_item` hook
	 */
	public static function onDirectoryItemEvent(DirectoryItemEvent $event): void
	{
		$hook_data = [
			'contact' => $event->getContactArray(),
			'entry'   => $event->getEntryArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setEntryArray($hook_data['entry'] ?? []);
	}

	/**
	 * Map the NotifierEndEvent to `notifier_end` hook
	 *
	 * The item array is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onNotifierEndEvent(NotifierEndEvent $event): void
	{
		static::callHook($event->getName(), $event->getItemArray());
	}

	/**
	 * Map the EnotifyEvent to `enotify` hook
	 *
	 * The data array is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onEnotifyEvent(EnotifyEvent $event): void
	{
		$event->setDataArray(
			static::callHook($event->getName(), $event->getDataArray()),
		);
	}

	/**
	 * Map the EnotifyStoreEvent to `enotify_store` hook
	 *
	 * The data array is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onEnotifyStoreEvent(EnotifyStoreEvent $event): void
	{
		$event->setDataArray(
			static::callHook($event->getName(), $event->getDataArray()),
		);
	}

	/**
	 * Map the EnotifyMailEvent to `enotify_mail` hook
	 *
	 * The data array is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onEnotifyMailEvent(EnotifyMailEvent $event): void
	{
		$event->setDataArray(
			static::callHook($event->getName(), $event->getDataArray()),
		);
	}

	/**
	 * Map the EditContactFormEvent to `contact_edit` hook
	 */
	public static function onEditContactFormEvent(EditContactFormEvent $event): void
	{
		$hook_data = [
			'contact' => $event->getContactArray(),
			'output'  => $event->getOutput(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['output']) && is_string($hook_data['output'])) {
			$event->setOutput($hook_data['output']);
		}
	}

	/**
	 * Map the EditContactPostEvent to `contact_edit_post` hook
	 *
	 * The request data is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onEditContactPostEvent(EditContactPostEvent $event): void
	{
		$event->setRequestArray(
			static::callHook($event->getName(), $event->getRequestArray()),
		);
	}

	public static function onConfigLoadedEvent(ConfigLoadedEvent $event): void
	{
		static::callHook($event->getName(), $event->getConfig());
	}

	public static function onCollectRoutesEvent(CollectRoutesEvent $event): void
	{
		$event->setRouteCollector(
			static::callHook($event->getName(), $event->getRouteCollector()),
		);
	}

	/**
	 * Map the PERMISSION_TOOLTIP_CONTENT event to `lockview_content` hook
	 */
	public static function onPermissionTooltipContentEvent(PermissionTooltipContentEvent $event): void
	{
		$event->setModelArray(
			(array) static::callHook($event->getName(), $event->getModelArray()),
		);
	}

	/**
	 * Map the InsertPostLocalEvent to `post_local` hook
	 */
	public static function onInsertPostLocalEvent(InsertPostLocalEvent $event): void
	{
		$event->setItemArray((array) static::callHook($event->getName(), $event->getItemArray()));
	}

	/**
	 * Map the InsertPostLocalStartEvent to `post_local_start` hook
	 *
	 * The request array is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onInsertPostLocalStartEvent(InsertPostLocalStartEvent $event): void
	{
		$event->setRequestArray(
			static::callHook($event->getName(), $event->getRequestArray()),
		);
	}

	/**
	 * Map the InsertPostLocalEndEvent to `post_local_end` hook
	 */
	public static function onInsertPostLocalEndEvent(InsertPostLocalEndEvent $event): void
	{
		$event->setItemArray((array) static::callHook($event->getName(), $event->getItemArray()));
	}

	/**
	 * Map the InsertPostRemoteEvent to `post_remote` hook
	 */
	public static function onInsertPostRemoteEvent(InsertPostRemoteEvent $event): void
	{
		$event->setItemArray((array) static::callHook($event->getName(), $event->getItemArray()));
	}

	/**
	 * Map the InsertPostRemoteEndEvent to `post_remote_end` hook
	 */
	public static function onInsertPostRemoteEndEvent(InsertPostRemoteEndEvent $event): void
	{
		$event->setItemArray((array) static::callHook($event->getName(), $event->getItemArray()));
	}

	/**
	 * Map the PreparePostStartEvent to `prepare_body_init` hook
	 */
	public static function onPreparePostStartEvent(PreparePostStartEvent $event): void
	{
		$event->setItemArray((array) static::callHook($event->getName(), $event->getItemArray()));
	}

	/**
	 * Map the PreparePostFilterContentEvent to `prepare_body_content_filter` hook
	 */
	public static function onPreparePostFilterContentEvent(PreparePostFilterContentEvent $event): void
	{
		$hook_data = [
			'item'           => $event->getItemArray(),
			'uid'            => $event->getUserId(),
			'filter_reasons' => $event->getFilterReasons(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setFilterReasons($hook_data['filter_reasons'] ?? []);
	}

	/**
	 * Map the PreparePostEvent to `prepare_body` hook
	 */
	public static function onPreparePostEvent(PreparePostEvent $event): void
	{
		$hook_data = [
			'item'           => $event->getItemArray(),
			'html'           => $event->getHtml(),
			'preview'        => $event->isPreview(),
			'filter_reasons' => $event->getFilterReasons(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setHtml($hook_data['html'] ?? '');
	}

	/**
	 * Map the PreparePostEndEvent to `prepare_body_final` hook
	 */
	public static function onPreparePostEndEvent(PreparePostEndEvent $event): void
	{
		$hook_data = [
			'item' => $event->getItemArray(),
			'html' => $event->getHtml(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setHtml($hook_data['html'] ?? '');
	}

	/**
	 * Map the PhotoUploadStartEvent to `photo_post_init` hook
	 */
	public static function onPhotoUploadStartEvent(PhotoUploadStartEvent $event): void
	{
		$event->setRequestArray(
			static::callHook($event->getName(), $event->getRequestArray()),
		);
	}

	/**
	 * Map the PhotoUploadEvent to `photo_post_file` hook
	 */
	public static function onPhotoUploadEvent(PhotoUploadEvent $event): void
	{
		$data = [
			'src'      => $event->getSrc(),
			'filename' => $event->getFilename(),
			'filesize' => $event->getFilesize(),
			'type'     => $event->getType(),
		];

		$data = static::callHook($event->getName(), $data);

		$event->setSrc($data['src']);
		$event->setFilename($data['filename']);
		$event->setFilesize((int) $data['filesize']);
		$event->setType($data['type']);
	}

	/**
	 * Map the PhotoUploadFormEvent to `photo_upload_form` hook
	 *
	 * The form data is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onPhotoUploadFormEvent(PhotoUploadFormEvent $event): void
	{
		$event->setFormArray(
			static::callHook($event->getName(), $event->getFormArray()),
		);
	}

	/**
	 * Map the PhotoUploadEndEvent to `photo_post_end` hook
	 */
	public static function onPhotoUploadEndEvent(PhotoUploadEndEvent $event): void
	{
		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), $event->getId());
	}

	/**
	 * Map the ProfileSidebarEvent event to `profile_sidebar` hook
	 */
	public static function onProfileSidebarEvent(ProfileSidebarEvent $event): void
	{
		$hook_data = [
			'profile' => $event->getProfileArray(),
			'entry'   => $event->getEntry(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setEntry($hook_data['entry'] ?? $event->getEntry());
	}

	/**
	 * Map the ProfileSettingsFormEvent to `profile_edit` hook
	 */
	public static function onProfileSettingsFormEvent(ProfileSettingsFormEvent $event): void
	{
		$hook_data = [
			'profile' => $event->getProfileArray(),
			'entry'   => $event->getEntry(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['entry']) && is_string($hook_data['entry'])) {
			$event->setEntry($hook_data['entry']);
		}
	}

	/**
	 * Map the ProfileSettingsPostEvent to `profile_post` hook
	 *
	 * The request data is passed as the whole hook data to stay backward-compatible.
	 */
	public static function onProfileSettingsPostEvent(ProfileSettingsPostEvent $event): void
	{
		$event->setRequestArray(
			static::callHook($event->getName(), $event->getRequestArray()),
		);
	}

	/**
	 * Map the ProfileSidebarStartEvent event to `profile_sidebar_enter` hook
	 */
	public static function onProfileSidebarStartEvent(ProfileSidebarStartEvent $event): void
	{
		$profile = static::callHook($event->getName(), $event->getProfileArray());

		if (isset($profile)) {
			$event->setProfileArray($profile);
		}
	}

	/**
	 * Map the ProfileTabsEvent to `profile_tabs` hook
	 *
	 * The tabs list is the only data that can be modified.
	 */
	public static function onProfileTabsEvent(ProfileTabsEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'is_owner' => $event->isOwner(),
			'nickname' => $event->getNickname(),
			'tab'      => $event->getTab(),
			'tabs'     => $event->getTabsArray(),
		]);

		if (isset($data['tabs'])) {
			$event->setTabsArray($data['tabs']);
		}
	}

	/**
	 * Map the BbcodeToHtmlStartEvent event to `bbcode` hook
	 */
	public static function onBbcodeToHtmlEvent(BbcodeToHtmlStartEvent $event): void
	{
		$event->setBbcode2html(
			static::callHook($event->getName(), $event->getBbcode2html()),
		);
	}

	/**
	 * Map the HtmlToBbcodeEndEvent event to `html2bbcode` hook
	 */
	public static function onHtmlToBbcodeEvent(HtmlToBbcodeEndEvent $event): void
	{
		$event->setHtml2bbcode(
			static::callHook($event->getName(), $event->getHtml2bbcode()),
		);
	}

	/**
	 * Map the BbcodeToMarkdownEndEvent event to `bb2diaspora` hook
	 */
	public static function onBbcodeToMarkdownEndEvent(BbcodeToMarkdownEndEvent $event): void
	{
		$event->setBbcode2markdown(
			static::callHook($event->getName(), $event->getBbcode2markdown()),
		);
	}

	/**
	 * Map the BlockContactEvent to `block` hook
	 */
	public static function onBlockContactEvent(BlockContactEvent $event): void
	{
		$hook_data = [
			'contact' => $event->getContactArray(),
			'uid'     => $event->getUid(),
			'result'  => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	/**
	 * Map the AppMenuEvent to `app_menu` hook
	 */
	public static function onAppMenuEvent(AppMenuEvent $event): void
	{
		$hook_data = [
			'app_menu' => $event->getAppMenuArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setAppMenuArray((array) $hook_data['app_menu']);
	}

	/**
	 * Map the AvatarLookupEvent to `avatar_lookup` hook
	 */
	public static function onAvatarLookupEvent(AvatarLookupEvent $event): void
	{
		$hook_data = [
			'size'    => $event->getSize(),
			'email'   => $event->getEmail(),
			'url'     => $event->getUrl(),
			'success' => $event->isSuccess(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setUrl((string) $hook_data['url']);
		$event->setSuccess((bool) $hook_data['success']);
	}

	/**
	 * Map the ProbeDetectEvent to `probe_detect` hook
	 */
	public static function onProbeDetectEvent(ProbeDetectEvent $event): void
	{
		$hook_data = [
			'uri'     => $event->getUri(),
			'network' => $event->getNetwork(),
			'uid'     => $event->getUid(),
			'result'  => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult(is_array($hook_data['result']) ? $hook_data['result'] : false);
		}
	}

	/**
	 * Map the ProtocolSupportsFollowEvent to `support_follow` hook
	 */
	public static function onProtocolSupportsFollowEvent(ProtocolSupportsFollowEvent $event): void
	{
		$hook_data = [
			'protocol' => $event->getProtocol(),
			'result'   => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	/**
	 * Map the ProtocolSupportsProbeEvent to `support_probe` hook
	 */
	public static function onProtocolSupportsProbeEvent(ProtocolSupportsProbeEvent $event): void
	{
		$hook_data = [
			'protocol' => $event->getProtocol(),
			'result'   => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	/**
	 * Map the ProtocolSupportsRevokeFollowEvent to `support_revoke_follow` hook
	 */
	public static function onProtocolSupportsRevokeFollowEvent(ProtocolSupportsRevokeFollowEvent $event): void
	{
		$hook_data = [
			'protocol' => $event->getProtocol(),
			'result'   => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	/**
	 * Map the ACCOUNT_AUTHENTICATE event to `authenticate` hook
	 */
	public static function onAccountAuthenticateEvent(AccountAuthenticateEvent $event): void
	{
		$addon_auth = [
			'username'      => $event->getUsername(),
			'password'      => $event->getPassword(),
			'authenticated' => $event->isAuthenticated() ? 1 : 0,
			'user_record'   => $event->getUserRecordArray(),
		];

		$addon_auth = static::callHook($event->getName(), $addon_auth);

		$event->setAuthenticated(!empty($addon_auth['authenticated']));
		$event->setUserRecordArray($addon_auth['user_record'] ?? null);
	}

	/**
	 * Map the ACCOUNT_REGISTER_FORM event to `register_form` hook
	 */
	public static function onAccountRegisterFormEvent(AccountRegisterFormEvent $event): void
	{
		$template = $event->getMarkupTemplate();

		$template = static::callHook($event->getName(), $template);

		$event->setMarkupTemplate((string) $template);
	}

	/**
	 * Map the ACCOUNT_REGISTER_POST event to `register_post` hook
	 */
	public static function onAccountRegisterPostEvent(AccountRegisterPostEvent $event): void
	{
		$data = ['post' => $event->getPostArray()];
		$data = static::callHook($event->getName(), $data);
		$event->setPostArray($data['post'] ?? []);
	}

	/**
	 * Map the ACCOUNT_REGISTER event to `register_account` hook
	 */
	public static function onAccountRegisterEvent(AccountRegisterEvent $event): void
	{
		$event->setUserId((int) static::callHook($event->getName(), $event->getUserId()));
	}

	/**
	 * Map the ACCOUNT_REMOVE event to `remove_account` hook
	 */
	public static function onAccountRemoveEvent(AccountRemoveEvent $event): void
	{
		$event->setUserArray((array) static::callHook($event->getName(), $event->getUserArray()));
	}

	/**
	 * Map the AclLookupEndEvent to `acl_lookup_end` hook
	 */
	public static function onAclLookupEndEvent(AclLookupEndEvent $event): void
	{
		$hook_data = [
			'tot'      => $event->getTotal(),
			'start'    => $event->getStart(),
			'count'    => $event->getCount(),
			'circles'  => $event->getCircles(),
			'contacts' => $event->getContacts(),
			'items'    => $event->getItems(),
			'type'     => $event->getType(),
			'search'   => $event->getSearch(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setTotal((int) $hook_data['tot']);
		$event->setStart((int) $hook_data['start']);
		$event->setCount((int) $hook_data['count']);
		$event->setItems((array) $hook_data['items']);
	}

	/**
	 * Map the AddWorkerTaskEvent to `proc_run` hook
	 */
	public static function onAddWorkerTaskEvent(AddWorkerTaskEvent $event): void
	{
		$hook_data = [
			'args'    => $event->getArgsArray(),
			'run_cmd' => $event->isRunCmd(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setRunCmd((bool) ($hook_data['run_cmd'] ?? $event->isRunCmd()));
	}

	/**
	 * Map the AddonSettingsPostEvent to `addon_settings_post` hook
	 */
	public static function onAddonSettingsPostEvent(AddonSettingsPostEvent $event): void
	{
		static::callHook($event->getName(), $event->getRequestArray());
	}

	/**
	 * Map the LOGGED_IN event to `logged_in` hook
	 */
	public static function onLoggedInEvent(LoggedInEvent $event): void
	{
		static::callHook($event->getName(), $event->getRecordArray());
	}

	/**
	 * Map the MAGIC_AUTH_SUCCESS event to `magic_auth_success` hook
	 */
	public static function onMagicAuthSuccessEvent(MagicAuthSuccessEvent $event): void
	{
		$data = [
			'visitor' => $event->getVisitorArray(),
			'url'     => $event->getUrl(),
		];
		$data = static::callHook($event->getName(), $data);
		if (is_array($data['visitor'] ?? null)) {
			$event->setVisitorArray($data['visitor']);
		}
	}

	/**
	 * Map the ModerationUsersTabsEvent to `moderation_users_tabs` hook
	 */
	public static function onModerationUsersTabsEvent(ModerationUsersTabsEvent $event): void
	{
		$hook_data = [
			'tabs'        => $event->getTabsArray(),
			'selectedTab' => $event->getSelectedTab(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setTabsArray((array) $hook_data['tabs']);
	}

	/**
	 * Map the NavInfoEvent to `nav_info` hook
	 */
	public static function onNavInfoEvent(NavInfoEvent $event): void
	{
		$hook_data = [
			'banner'       => $event->getBanner(),
			'nav'          => $event->getNavArray(),
			'sitelocation' => $event->getSitelocation(),
			'userinfo'     => $event->getUserinfoArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setBanner((string) ($hook_data['banner'] ?? ''));
		$event->setNavArray((array) ($hook_data['nav'] ?? []));
		$event->setSitelocation((string) ($hook_data['sitelocation'] ?? ''));
		$event->setUserinfoArray(is_array($hook_data['userinfo'] ?? null) ? $hook_data['userinfo'] : null);
	}

	/**
	 * Map the ZRL_INIT event to `zrl_init` hook
	 */
	public static function onZrlInitEvent(ZrlInitEvent $event): void
	{
		static::callHook($event->getName(), [
			'zrl' => $event->getZrlUrl(),
			'url' => $event->getUrl(),
		]);
	}

	/**
	 * Map the EventCreatedEvent to `event_created` hook
	 */
	public static function onEventCreatedEvent(EventCreatedEvent $event): void
	{
		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), (int) ($event->getEventArray()['id'] ?? 0));
	}

	/**
	 * Map the EventUpdatedEvent to `event_updated` hook
	 */
	public static function onEventUpdatedEvent(EventUpdatedEvent $event): void
	{
		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), (int) ($event->getEventArray()['id'] ?? 0));
	}

	/**
	 * Map the LOGIN_FORM event to `login_hook` hook
	 *
	 * login_hook receives a string by reference, so we wrap/unwrap it in an array.
	 */
	public static function onLoginFormEvent(LoginFormEvent $event): void
	{
		$event->setHtml((string) static::callHook($event->getName(), $event->getHtml()));
	}

	/**
	 * Map the EmailerSendPrepareEvent to `emailer_send_prepare` hook
	 *
	 * emailer_send_prepare receives an IEmail object by reference, so we wrap/unwrap it.
	 */
	public static function onEmailerSendPrepareEvent(EmailerSendPrepareEvent $event): void
	{
		$email = static::callHook($event->getName(), $event->getEmail());

		$event->setEmail($email instanceof IEmail ? $email : null);
	}

	/**
	 * Map the EmailerSendEvent to `emailer_send` hook
	 */
	public static function onEmailerSendEvent(EmailerSendEvent $event): void
	{
		$hook_data = [
			'to'         => $event->getToAddress(),
			'subject'    => $event->getSubject(),
			'body'       => $event->getBody(),
			'headers'    => $event->getHeaders(),
			'parameters' => $event->getParameters(),
			'sent'       => $event->isSent(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setSent((bool) ($hook_data['sent'] ?? $event->isSent()));
	}

	/**
	 * Map the EmailGetMessageEvent to `email_getmessage` hook
	 */
	public static function onEmailGetMessageEvent(EmailGetMessageEvent $event): void
	{
		$hook_data = [
			'text' => $event->getText(),
			'html' => $event->getHtml(),
			'item' => $event->getItemArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setText((string) ($hook_data['text'] ?? $event->getText()));
		$event->setHtml((string) ($hook_data['html'] ?? $event->getHtml()));
		$event->setItemArray(is_array($hook_data['item'] ?? null) ? $hook_data['item'] : $event->getItemArray());
	}

	/**
	 * Map the EmailGetMessageEndEvent to `email_getmessage_end` hook
	 */
	public static function onEmailGetMessageEndEvent(EmailGetMessageEndEvent $event): void
	{
		$hook_data = $event->getItemArray();

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setItemArray(is_array($hook_data) ? $hook_data : $event->getItemArray());
	}

	public static function onArrayFilterEvent(ArrayFilterEvent $event): void
	{
		$event->setArray(
			static::callHook($event->getName(), $event->getArray()),
		);
	}

	/**
	 * Map the GenerateMapEvent to `generate_map` hook
	 */
	public static function onGenerateMapEvent(GenerateMapEvent $event): void
	{
		$hook_data = [
			'lat'  => $event->getLatitude(),
			'lon'  => $event->getLongitude(),
			'mode' => $event->getMode(),
			'html' => $event->getHtml(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setHtml((string) ($hook_data['html'] ?? $event->getHtml()));
	}

	/**
	 * Map the GenerateNamedMapEvent to `generate_named_map` hook
	 */
	public static function onGenerateNamedMapEvent(GenerateNamedMapEvent $event): void
	{
		$hook_data = [
			'location' => $event->getLocation(),
			'mode'     => $event->getMode(),
			'html'     => $event->getHtml(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setHtml((string) ($hook_data['html'] ?? $event->getHtml()));
	}

	/**
	 * Map the MapGetCoordinatesEvent to `Map::getCoordinates` hook
	 */
	public static function onMapGetCoordinatesEvent(MapGetCoordinatesEvent $event): void
	{
		$hook_data = [
			'location' => $event->getLocation(),
			'lat'      => $event->getLatitude()  ?? false,
			'lon'      => $event->getLongitude() ?? false,
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setLatitude(is_string($hook_data['lat'] ?? null) ? $hook_data['lat'] : null);
		$event->setLongitude(is_string($hook_data['lon'] ?? null) ? $hook_data['lon'] : null);
	}

	/**
	 * Map the OtherEncapsulateEvent to `other_encapsulate` hook
	 */
	public static function onOtherEncapsulateEvent(OtherEncapsulateEvent $event): void
	{
		$hook_data = [
			'data'   => $event->getData(),
			'pubkey' => $event->getPubkey(),
			'alg'    => $event->getAlg(),
			'result' => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setResult((string) ($hook_data['result'] ?? $event->getResult()));
	}

	/**
	 * Map the OtherUnencapsulateEvent to `other_unencapsulate` hook
	 */
	public static function onOtherUnencapsulateEvent(OtherUnencapsulateEvent $event): void
	{
		$hook_data = [
			'data'   => $event->getDataArray(),
			'prvkey' => $event->getPrivateKey(),
			'alg'    => $event->getAlg(),
			'result' => $event->getResultArray(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		$event->setResultArray(is_array($hook_data['result'] ?? null) ? $hook_data['result'] : $event->getResultArray());
	}

	public static function onNetworkToNameEvent(NetworkToNameEvent $event): void
	{
		$event->setNetworks(
			static::callHook($event->getName(), $event->getNetworks()),
		);
	}

	public static function onNetworkContentStartEvent(NetworkContentStartEvent $event): void
	{
		static::callHook($event->getName(), [
			'query' => $event->getQuery(),
		]);
	}

	public static function onNetworkContentTabsEvent(NetworkContentTabsEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'tabs' => $event->getTabs(),
		]);

		if (isset($data['tabs'])) {
			$event->setTabs($data['tabs']);
		}
	}

	public static function onParseLinkEvent(ParseLinkEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'url'    => $event->getUrl(),
			'format' => $event->getFormat(),
			'text'   => $event->getText(),
		]);

		if (isset($data['text'])) {
			$event->setText($data['text']);
		}
	}

	public static function onRenderLocationEvent(RenderLocationEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'location' => $event->getLocation(),
			'coord'    => $event->getCoord(),
			'html'     => $event->getHtml(),
		]);

		if (isset($data['html'])) {
			$event->setHtml($data['html']);
		}
	}

	/**
	 * Map the RevokeFollowContactEvent to `revoke_follow` hook
	 */
	public static function onRevokeFollowContactEvent(RevokeFollowContactEvent $event): void
	{
		$hook_data = [
			'contact' => $event->getContactArray(),
			'uid'     => $event->getUid(),
			'result'  => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	public static function onPageInfoEvent(PageInfoEvent $event): void
	{
		$event->setDataArray(
			static::callHook($event->getName(), $event->getDataArray()),
		);
	}

	public static function onSmileyListEvent(SmileyListEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'texts' => $event->getTexts(),
			'icons' => $event->getIcons(),
		]);

		if (isset($data['texts'])) {
			$event->setTexts($data['texts']);
		}

		if (isset($data['icons'])) {
			$event->setIcons($data['icons']);
		}
	}

	/**
	 * Map the StorageConfigEvent to `storage_config` hook
	 */
	public static function onStorageConfigEvent(StorageConfigEvent $event): void
	{
		$hook_data = [
			'name'           => $event->getBackendName(),
			'storage_config' => $event->getConfig(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['storage_config'])) {
			$event->setConfig($hook_data['storage_config']);
		}
	}

	/**
	 * Map the StorageInstanceEvent to `storage_instance` hook
	 */
	public static function onStorageInstanceEvent(StorageInstanceEvent $event): void
	{
		$hook_data = [
			'name'    => $event->getBackendName(),
			'storage' => $event->getStorage(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['storage'])) {
			$event->setStorage($hook_data['storage']);
		}
	}

	public static function onTemplateVarsEvent(TemplateVarsEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'template' => $event->getTemplate(),
			'vars'     => $event->getVars(),
		]);

		if (isset($data['vars'])) {
			$event->setVars($data['vars']);
		}
	}

	/**
	 * Map the UnfollowContactEvent to `unfollow` hook
	 */
	public static function onUnfollowContactEvent(UnfollowContactEvent $event): void
	{
		$hook_data = [
			'contact' => $event->getContactArray(),
			'uid'     => $event->getUid(),
			'result'  => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	/**
	 * Map the UnblockContactEvent to `unblock` hook
	 */
	public static function onUnblockContactEvent(UnblockContactEvent $event): void
	{
		$hook_data = [
			'contact' => $event->getContactArray(),
			'uid'     => $event->getUid(),
			'result'  => $event->getResult(),
		];

		$hook_data = static::callHook($event->getName(), $hook_data);

		if (isset($hook_data['result'])) {
			$event->setResult((bool) $hook_data['result']);
		}
	}

	/**
	 * Map the UserExportOptionsEvent to `uexport_options` hook
	 */
	public static function onUserExportOptionsEvent(UserExportOptionsEvent $event): void
	{
		$event->setOptionsArray(
			static::callHook($event->getName(), $event->getOptionsArray()),
		);
	}

	public static function onContactPhotoMenuEvent(ContactPhotoMenuEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'contact' => $event->getContact(),
			'menu'    => $event->getMenu(),
		]);

		if (isset($data['menu'])) {
			$event->setMenu($data['menu']);
		}
	}

	public static function onJotNetworksEvent(JotNetworksEvent $event): void
	{
		$event->setJotnetsFields(
			static::callHook($event->getName(), $event->getJotnetsFields()),
		);
	}

	public static function onOcrDetectionEvent(OcrDetectionEvent $event): void
	{
		$data = static::callHook($event->getName(), [
			'img_str'     => $event->getImgStr(),
			'description' => $event->getDescription(),
		]);

		if (isset($data['description'])) {
			$event->setDescription($data['description']);
		}
	}

	public static function onHtmlFilterEvent(HtmlFilterEvent $event): void
	{
		$event->setHtml(
			static::callHook($event->getName(), $event->getHtml()),
		);
	}

	/**
	 * Map the HeadEvent to `head` hook
	 */
	public static function onHeadEvent(HeadEvent $event): void
	{
		$event->setHtml(static::callHook($event->getName(), $event->getHtml()));
	}

	/**
	 * Map the FooterEvent to `footer` hook
	 */
	public static function onFooterEvent(FooterEvent $event): void
	{
		$event->setHtml(static::callHook($event->getName(), $event->getHtml()));
	}

	/**
	 * Map the PageHeaderEvent to `page_header` hook
	 */
	public static function onPageHeaderEvent(PageHeaderEvent $event): void
	{
		$event->setHtml(static::callHook($event->getName(), $event->getHtml()));
	}

	public static function onModuleInitEvent(ModuleInitEvent $event): void
	{
		static::callHook($event->getModuleName() . '_mod_init', '');
	}

	public static function onModulePostEvent(ModulePostEvent $event): void
	{
		$event->setPost(
			static::callHook($event->getModuleName() . '_mod_post', $event->getPost()),
		);
	}

	public static function onModuleContentEvent(ModuleContentEvent $event): void
	{
		$arr = ['content' => $event->getContent()];
		$arr = static::callHook($event->getModuleClass() . '_mod_content', $arr);
		$event->setContent($arr['content']);
	}

	public static function onModulePostRecipientEvent(ModulePostRecipientEvent $event): void
	{
		$event->setHtml(
			static::callHook($event->getModuleName() . '_post_recipient', $event->getHtml()),
		);
	}

	/**
	 * @param int|string|array|object $data
	 *
	 * @return int|string|array|object
	 */
	private static function callHook(string $name, $data)
	{
		// If possible, map the event name to the legacy Hook name
		$name = static::$eventMapper[$name] ?? $name;

		// Little hack to allow mocking the Hook call in tests.
		if (static::$mockedCallHook instanceof \Closure) {
			return (static::$mockedCallHook)->__invoke($name, $data);
		}

		Hook::callAll($name, $data);

		return $data;
	}
}
