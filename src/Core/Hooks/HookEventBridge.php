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
use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\AvatarLookupEvent;
use Friendica\Event\BbcodeToHtmlStartEvent;
use Friendica\Event\BbcodeToMarkdownEndEvent;
use Friendica\Event\BlockContactEvent;
use Friendica\Event\CacheItemEvent;
use Friendica\Event\CheckItemNotificationEvent;
use Friendica\Event\ContactPhotoMenuEvent;
use Friendica\Event\ConversationStartEvent;
use Friendica\Event\DetectLanguagesEvent;
use Friendica\Event\DirectoryItemEvent;
use Friendica\Event\DisplayItemEvent;
use Friendica\Event\EnotifyEvent;
use Friendica\Event\EnotifyMailEvent;
use Friendica\Event\EnotifyStoreEvent;
use Friendica\Event\EditContactFormEvent;
use Friendica\Event\EditContactPostEvent;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Event\FeatureEnabledEvent;
use Friendica\Event\FeatureGetEvent;
use Friendica\Event\FollowContactEvent;
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
use Friendica\Event\PageInfoEvent;
use Friendica\Event\ParseLinkEvent;
use Friendica\Event\RenderLocationEvent;
use Friendica\Event\RevokeFollowContactEvent;
use Friendica\Event\SmileyListEvent;
use Friendica\Event\TemplateVarsEvent;
use Friendica\Event\UnblockContactEvent;
use Friendica\Event\UnfollowContactEvent;
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
		InitEvent::NAME                              => 'init_1',
		HomeInitEvent::NAME                          => 'home_init',
		LoggingOutEvent::NAME                        => 'logging_out',
		ConfigLoadedEvent::NAME                      => 'load_config',
		CollectRoutesEvent::NAME                     => 'route_collection',
		AccountAuthenticateEvent::NAME               => 'authenticate',
		AccountRegisterEvent::NAME                   => 'register_account',
		AccountRegisterFormEvent::NAME               => 'register_form',
		AccountRegisterPostEvent::NAME               => 'register_post',
		AccountRemoveEvent::NAME                     => 'remove_user',
		ArrayFilterEvent::ACL_LOOKUP_END             => 'acl_lookup_end',
		ArrayFilterEvent::ADD_WORKER_TASK            => 'proc_run',
		ArrayFilterEvent::ADDON_SETTINGS_POST        => 'addon_settings_post',
		ArrayFilterEvent::APP_MENU                   => 'app_menu',
		AvatarLookupEvent::NAME                      => 'avatar_lookup',
		BbcodeToHtmlStartEvent::NAME                 => 'bbcode',
		BbcodeToMarkdownEndEvent::NAME               => 'bb2diaspora',
		BlockContactEvent::NAME                      => 'block',
		CacheItemEvent::NAME                         => 'put_item_in_cache',
		CheckItemNotificationEvent::NAME             => 'check_item_notification',
		ArrayFilterEvent::CONNECTOR_SETTINGS_POST    => 'connector_settings_post',
		ContactPhotoMenuEvent::NAME                  => 'contact_photo_menu',
		ConversationStartEvent::NAME                 => 'conversation_start',
		ArrayFilterEvent::DB_STRUCTURE_DEFINITION    => 'dbstructure_definition',
		ArrayFilterEvent::DB_VIEW_DEFINITION         => 'dbview_definition',
		DetectLanguagesEvent::NAME                   => 'detect_languages',
		DirectoryItemEvent::NAME                     => 'directory_item',
		DisplayItemEvent::NAME                       => 'display_item',
		ArrayFilterEvent::DISPLAY_SETTINGS_POST      => 'display_settings_post',
		EditContactFormEvent::NAME                   => 'contact_edit',
		EditContactPostEvent::NAME                   => 'contact_edit_post',
		ArrayFilterEvent::EMAIL_GET_MESSAGE          => 'email_getmessage',
		ArrayFilterEvent::EMAIL_GET_MESSAGE_END      => 'email_getmessage_end',
		ArrayFilterEvent::EMAILER_SEND               => 'emailer_send',
		ArrayFilterEvent::EMAILER_SEND_PREPARE       => 'emailer_send_prepare',
		EnotifyEvent::NAME                           => 'enotify',
		EnotifyMailEvent::NAME                       => 'enotify_mail',
		EnotifyStoreEvent::NAME                      => 'enotify_store',
		ArrayFilterEvent::EVENT_CREATED              => 'event_created',
		ArrayFilterEvent::EVENT_UPDATED              => 'event_updated',
		FeatureEnabledEvent::NAME                    => 'isEnabled',
		FeatureGetEvent::NAME                        => 'get',
		FetchItemByLinkEvent::NAME                   => 'item_by_link',
		FollowContactEvent::NAME                     => 'follow',
		ArrayFilterEvent::GENERATE_MAP               => 'generate_map',
		ArrayFilterEvent::GENERATE_NAMED_MAP         => 'generate_named_map',
		ArrayFilterEvent::GET_SITE_INFO              => 'getsiteinfo',
		ArrayFilterEvent::GLOBAL_DIR_UPDATE          => 'globaldir_update',
		HtmlToBbcodeEndEvent::NAME                   => 'html2bbcode',
		InsertPostLocalEvent::NAME                   => 'post_local',
		InsertPostLocalEndEvent::NAME                => 'post_local_end',
		InsertPostRemoteEvent::NAME                  => 'post_remote',
		InsertPostRemoteEndEvent::NAME               => 'post_remote_end',
		InsertPostLocalStartEvent::NAME              => 'post_local_start',
		ItemPhotoMenuEvent::NAME                     => 'item_photo_menu',
		ItemTaggedEvent::NAME                        => 'tagged',
		JotNetworksEvent::NAME                       => 'jot_networks',
		LoggedInEvent::NAME                          => 'logged_in',
		LoginFormEvent::NAME                         => 'login_hook',
		MagicAuthSuccessEvent::NAME                  => 'magic_auth_success',
		ArrayFilterEvent::MAP_GET_COORDINATES        => 'Map::getCoordinates',
		ArrayFilterEvent::MODERATION_USERS_TABS      => 'moderation_users_tabs',
		ArrayFilterEvent::NAV_INFO                   => 'nav_info',
		NetworkContentStartEvent::NAME               => 'network_content_init',
		NetworkContentTabsEvent::NAME                => 'network_tabs',
		NetworkToNameEvent::NAME                     => 'network_to_name',
		NotifierEndEvent::NAME                       => 'notifier_end',
		OcrDetectionEvent::NAME                      => 'ocr-detection',
		ArrayFilterEvent::OTHER_ENCAPSULATE          => 'other_encapsulate',
		ArrayFilterEvent::OTHER_UNENCAPSULATE        => 'other_unencapsulate',
		PageInfoEvent::NAME                          => 'page_info_data',
		ParseLinkEvent::NAME                         => 'parse_link',
		ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT => 'lockview_content',
		PhotoUploadEvent::NAME                       => 'photo_post_file',
		PhotoUploadEndEvent::NAME                    => 'photo_post_end',
		PhotoUploadFormEvent::NAME                   => 'photo_upload_form',
		PhotoUploadStartEvent::NAME                  => 'photo_post_init',
		PreparePostEvent::NAME                       => 'prepare_body',
		PreparePostEndEvent::NAME                    => 'prepare_body_final',
		PreparePostFilterContentEvent::NAME          => 'prepare_body_content_filter',
		PreparePostStartEvent::NAME                  => 'prepare_body_init',
		ProbeDetectEvent::NAME                       => 'probe_detect',
		ProfileSettingsFormEvent::NAME               => 'profile_edit',
		ProfileSettingsPostEvent::NAME               => 'profile_post',
		ProfileSidebarEvent::NAME                    => 'profile_sidebar',
		ProfileSidebarStartEvent::NAME               => 'profile_sidebar_enter',
		ProfileTabsEvent::NAME                       => 'profile_tabs',
		ProtocolSupportsFollowEvent::NAME            => 'support_follow',
		ProtocolSupportsProbeEvent::NAME             => 'support_probe',
		ProtocolSupportsRevokeFollowEvent::NAME      => 'support_revoke_follow',
		RenderLocationEvent::NAME                    => 'render_location',
		RevokeFollowContactEvent::NAME               => 'revoke_follow',
		SmileyListEvent::NAME                        => 'smilie',
		ArrayFilterEvent::STORAGE_CONFIG             => 'storage_config',
		ArrayFilterEvent::STORAGE_INSTANCE           => 'storage_instance',
		TemplateVarsEvent::NAME                      => 'template_vars',
		UnblockContactEvent::NAME                    => 'unblock',
		UnfollowContactEvent::NAME                   => 'unfollow',
		ArrayFilterEvent::USER_EXPORT_OPTIONS        => 'uexport_options',
		ZrlInitEvent::NAME                           => 'zrl_init',
		HtmlFilterEvent::HEAD                        => 'head',
		HtmlFilterEvent::FOOTER                      => 'footer',
		HtmlFilterEvent::PAGE_HEADER                 => 'page_header',
		HtmlFilterEvent::PAGE_CONTENT_TOP            => 'page_content_top',
		HtmlFilterEvent::PAGE_END                    => 'page_end',
		HtmlFilterEvent::MOD_HOME_CONTENT            => 'home_content',
		HtmlFilterEvent::MOD_ABOUT_CONTENT           => 'about_hook',
		HtmlFilterEvent::MOD_PROFILE_CONTENT         => 'profile_advanced',
		HtmlFilterEvent::JOT_TOOL                    => 'jot_tool',
		HtmlFilterEvent::CONTACT_BLOCK_END           => 'contact_block_end',
	];

	/**
	 * @return array<string, string>
	 */
	public static function getStaticSubscribedEvents(): array
	{
		return [
			InitEvent::NAME                              => 'onNamedEvent',
			HomeInitEvent::NAME                          => 'onNamedEvent',
			LoggingOutEvent::NAME                        => 'onNamedEvent',
			ConfigLoadedEvent::NAME                      => 'onConfigLoadedEvent',
			CollectRoutesEvent::NAME                     => 'onCollectRoutesEvent',
			AccountAuthenticateEvent::NAME               => 'onAccountAuthenticateEvent',
			AccountRegisterEvent::NAME                   => 'onAccountRegisterEvent',
			AccountRegisterFormEvent::NAME               => 'onAccountRegisterFormEvent',
			AccountRegisterPostEvent::NAME               => 'onAccountRegisterPostEvent',
			AccountRemoveEvent::NAME                     => 'onAccountRemoveEvent',
			ArrayFilterEvent::ACL_LOOKUP_END             => 'onArrayFilterEvent',
			ArrayFilterEvent::ADD_WORKER_TASK            => 'onArrayFilterEvent',
			ArrayFilterEvent::ADDON_SETTINGS_POST        => 'onArrayFilterEvent',
			ArrayFilterEvent::APP_MENU                   => 'onArrayFilterEvent',
			AvatarLookupEvent::NAME                      => 'onAvatarLookupEvent',
			BbcodeToHtmlStartEvent::NAME                 => 'onBbcodeToHtmlEvent',
			BbcodeToMarkdownEndEvent::NAME               => 'onBbcodeToMarkdownEndEvent',
			BlockContactEvent::NAME                      => 'onBlockContactEvent',
			CacheItemEvent::NAME                         => 'onCacheItemEvent',
			CheckItemNotificationEvent::NAME             => 'onCheckItemNotificationEvent',
			ArrayFilterEvent::CONNECTOR_SETTINGS_POST    => 'onArrayFilterEvent',
			ContactPhotoMenuEvent::NAME                  => 'onContactPhotoMenuEvent',
			ConversationStartEvent::NAME                 => 'onConversationStartEvent',
			ArrayFilterEvent::DB_STRUCTURE_DEFINITION    => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_VIEW_DEFINITION         => 'onArrayFilterEvent',
			DetectLanguagesEvent::NAME                   => 'onDetectLanguagesEvent',
			DirectoryItemEvent::NAME                     => 'onDirectoryItemEvent',
			DisplayItemEvent::NAME                       => 'onDisplayItemEvent',
			ArrayFilterEvent::DISPLAY_SETTINGS_POST      => 'onArrayFilterEvent',
			EditContactFormEvent::NAME                   => 'onEditContactFormEvent',
			EditContactPostEvent::NAME                   => 'onEditContactPostEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE          => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE_END      => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAILER_SEND               => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAILER_SEND_PREPARE       => 'onEmailerSendPrepareEvent',
			EnotifyEvent::NAME                           => 'onEnotifyEvent',
			EnotifyMailEvent::NAME                       => 'onEnotifyMailEvent',
			EnotifyStoreEvent::NAME                      => 'onEnotifyStoreEvent',
			ArrayFilterEvent::EVENT_CREATED              => 'onEventCreatedEvent',
			ArrayFilterEvent::EVENT_UPDATED              => 'onEventUpdatedEvent',
			FeatureEnabledEvent::NAME                    => 'onFeatureEnabledEvent',
			FeatureGetEvent::NAME                        => 'onFeatureGetEvent',
			FetchItemByLinkEvent::NAME                   => 'onFetchItemByLinkEvent',
			FollowContactEvent::NAME                     => 'onFollowContactEvent',
			ArrayFilterEvent::GENERATE_MAP               => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_NAMED_MAP         => 'onArrayFilterEvent',
			ArrayFilterEvent::GET_SITE_INFO              => 'onArrayFilterEvent',
			ArrayFilterEvent::GLOBAL_DIR_UPDATE          => 'onArrayFilterEvent',
			HtmlToBbcodeEndEvent::NAME                   => 'onHtmlToBbcodeEvent',
			InsertPostLocalEvent::NAME                   => 'onInsertPostLocalEvent',
			InsertPostLocalEndEvent::NAME                => 'onInsertPostLocalEndEvent',
			InsertPostRemoteEvent::NAME                  => 'onInsertPostRemoteEvent',
			InsertPostRemoteEndEvent::NAME               => 'onInsertPostRemoteEndEvent',
			InsertPostLocalStartEvent::NAME              => 'onInsertPostLocalStartEvent',
			ItemPhotoMenuEvent::NAME                     => 'onItemPhotoMenuEvent',
			ItemTaggedEvent::NAME                        => 'onItemTaggedEvent',
			JotNetworksEvent::NAME                       => 'onJotNetworksEvent',
			LoggedInEvent::NAME                          => 'onLoggedInEvent',
			LoginFormEvent::NAME                         => 'onLoginFormEvent',
			MagicAuthSuccessEvent::NAME                  => 'onMagicAuthSuccessEvent',
			ArrayFilterEvent::MAP_GET_COORDINATES        => 'onArrayFilterEvent',
			ArrayFilterEvent::MODERATION_USERS_TABS      => 'onArrayFilterEvent',
			ArrayFilterEvent::NAV_INFO                   => 'onArrayFilterEvent',
			NetworkContentStartEvent::NAME               => 'onNetworkContentStartEvent',
			NetworkContentTabsEvent::NAME                => 'onNetworkContentTabsEvent',
			NetworkToNameEvent::NAME                     => 'onNetworkToNameEvent',
			NotifierEndEvent::NAME                       => 'onNotifierEndEvent',
			OcrDetectionEvent::NAME                      => 'onOcrDetectionEvent',
			ArrayFilterEvent::OTHER_ENCAPSULATE          => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_UNENCAPSULATE        => 'onArrayFilterEvent',
			PageInfoEvent::NAME                          => 'onPageInfoEvent',
			ParseLinkEvent::NAME                         => 'onParseLinkEvent',
			ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT => 'onPermissionTooltipContentEvent',
			PhotoUploadEvent::NAME                       => 'onPhotoUploadEvent',
			PhotoUploadEndEvent::NAME                    => 'onPhotoUploadEndEvent',
			PhotoUploadFormEvent::NAME                   => 'onPhotoUploadFormEvent',
			PhotoUploadStartEvent::NAME                  => 'onPhotoUploadStartEvent',
			PreparePostEvent::NAME                       => 'onPreparePostEvent',
			PreparePostEndEvent::NAME                    => 'onPreparePostEndEvent',
			PreparePostFilterContentEvent::NAME          => 'onPreparePostFilterContentEvent',
			PreparePostStartEvent::NAME                  => 'onPreparePostStartEvent',
			ProbeDetectEvent::NAME                       => 'onProbeDetectEvent',
			ProfileSettingsFormEvent::NAME               => 'onProfileSettingsFormEvent',
			ProfileSettingsPostEvent::NAME               => 'onProfileSettingsPostEvent',
			ProfileSidebarEvent::NAME                    => 'onProfileSidebarEvent',
			ProfileSidebarStartEvent::NAME               => 'onProfileSidebarStartEvent',
			ProfileTabsEvent::NAME                       => 'onProfileTabsEvent',
			ProtocolSupportsFollowEvent::NAME            => 'onProtocolSupportsFollowEvent',
			ProtocolSupportsProbeEvent::NAME             => 'onProtocolSupportsProbeEvent',
			ProtocolSupportsRevokeFollowEvent::NAME      => 'onProtocolSupportsRevokeFollowEvent',
			RenderLocationEvent::NAME                    => 'onRenderLocationEvent',
			RevokeFollowContactEvent::NAME               => 'onRevokeFollowContactEvent',
			SmileyListEvent::NAME                        => 'onSmileyListEvent',
			ArrayFilterEvent::STORAGE_CONFIG             => 'onArrayFilterEvent',
			ArrayFilterEvent::STORAGE_INSTANCE           => 'onArrayFilterEvent',
			TemplateVarsEvent::NAME                      => 'onTemplateVarsEvent',
			UnblockContactEvent::NAME                    => 'onUnblockContactEvent',
			UnfollowContactEvent::NAME                   => 'onUnfollowContactEvent',
			ArrayFilterEvent::USER_EXPORT_OPTIONS        => 'onArrayFilterEvent',
			ZrlInitEvent::NAME                           => 'onZrlInitEvent',
			HtmlFilterEvent::CONTACT_BLOCK_END           => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER                      => 'onHtmlFilterEvent',
			HtmlFilterEvent::HEAD                        => 'onHtmlFilterEvent',
			HtmlFilterEvent::JOT_TOOL                    => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_ABOUT_CONTENT           => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_HOME_CONTENT            => 'onHtmlFilterEvent',
			HtmlFilterEvent::MOD_PROFILE_CONTENT         => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP            => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END                    => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_HEADER                 => 'onHtmlFilterEvent',
			ModuleContentEvent::NAME                     => 'onModuleContentEvent',
			ModuleInitEvent::NAME                        => 'onModuleInitEvent',
			ModulePostEvent::NAME                        => 'onModulePostEvent',
			ModulePostRecipientEvent::NAME               => 'onModulePostRecipientEvent',
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
	public static function onPermissionTooltipContentEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$model = $data['model'] ?? [];

		$data['model'] = static::callHook($event->getName(), (array) $model);

		$event->setArray($data);
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
	 * Map the EVENT_CREATED event to `event_created` hook
	 */
	public static function onEventCreatedEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$id = $data['event']['id'] ?? 0;

		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), (int) $id);
	}

	/**
	 * Map the EVENT_UPDATED event to `event_updated` hook
	 */
	public static function onEventUpdatedEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$id = $data['event']['id'] ?? 0;

		// one-way-event: we don't care about the  returned value
		static::callHook($event->getName(), (int) $id);
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
	 * Map the EMAILER_SEND_PREPARE event to `emailer_send_prepare` hook
	 *
	 * emailer_send_prepare receives an IEmail object by reference, so we wrap/unwrap it.
	 */
	public static function onEmailerSendPrepareEvent(ArrayFilterEvent $event): void
	{
		$data          = $event->getArray();
		$data['email'] = static::callHook($event->getName(), $data['email'] ?? null);
		$event->setArray($data);
	}

	public static function onArrayFilterEvent(ArrayFilterEvent $event): void
	{
		$event->setArray(
			static::callHook($event->getName(), $event->getArray()),
		);
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
