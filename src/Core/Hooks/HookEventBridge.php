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
use Friendica\Event\CollectRoutesEvent;
use Friendica\Event\LoggedInEvent;
use Friendica\Event\LoginFormEvent;
use Friendica\Event\MagicAuthSuccessEvent;
use Friendica\Event\ZrlInitEvent;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Event\HomeInitEvent;
use Friendica\Event\InitEvent;
use Friendica\Event\LoggingOutEvent;
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
		InitEvent::NAME                                   => 'init_1',
		HomeInitEvent::NAME                               => 'home_init',
		LoggingOutEvent::NAME                             => 'logging_out',
		ConfigLoadedEvent::NAME                           => 'load_config',
		CollectRoutesEvent::NAME                          => 'route_collection',
		AccountAuthenticateEvent::NAME                    => 'authenticate',
		AccountRegisterEvent::NAME                        => 'register_account',
		AccountRegisterFormEvent::NAME                    => 'register_form',
		AccountRegisterPostEvent::NAME                    => 'register_post',
		AccountRemoveEvent::NAME                          => 'remove_user',
		ArrayFilterEvent::ACL_LOOKUP_END                  => 'acl_lookup_end',
		ArrayFilterEvent::ADD_WORKER_TASK                 => 'proc_run',
		ArrayFilterEvent::ADDON_SETTINGS_POST             => 'addon_settings_post',
		ArrayFilterEvent::APP_MENU                        => 'app_menu',
		ArrayFilterEvent::AVATAR_LOOKUP                   => 'avatar_lookup',
		ArrayFilterEvent::BBCODE_TO_HTML_START            => 'bbcode',
		ArrayFilterEvent::BBCODE_TO_MARKDOWN_END          => 'bb2diaspora',
		ArrayFilterEvent::BLOCK_CONTACT                   => 'block',
		ArrayFilterEvent::CACHE_ITEM                      => 'put_item_in_cache',
		ArrayFilterEvent::CHECK_ITEM_NOTIFICATION         => 'check_item_notification',
		ArrayFilterEvent::CONNECTOR_SETTINGS_POST         => 'connector_settings_post',
		ArrayFilterEvent::CONTACT_PHOTO_MENU              => 'contact_photo_menu',
		ArrayFilterEvent::CONVERSATION_START              => 'conversation_start',
		ArrayFilterEvent::DB_STRUCTURE_DEFINITION         => 'dbstructure_definition',
		ArrayFilterEvent::DB_VIEW_DEFINITION              => 'dbview_definition',
		ArrayFilterEvent::DETECT_LANGUAGES                => 'detect_languages',
		ArrayFilterEvent::DIRECTORY_ITEM                  => 'directory_item',
		ArrayFilterEvent::DISPLAY_ITEM                    => 'display_item',
		ArrayFilterEvent::DISPLAY_SETTINGS_POST           => 'display_settings_post',
		ArrayFilterEvent::EDIT_CONTACT_FORM               => 'contact_edit',
		ArrayFilterEvent::EDIT_CONTACT_POST               => 'contact_edit_post',
		ArrayFilterEvent::EMAIL_GET_MESSAGE               => 'email_getmessage',
		ArrayFilterEvent::EMAIL_GET_MESSAGE_END           => 'email_getmessage_end',
		ArrayFilterEvent::EMAILER_SEND                    => 'emailer_send',
		ArrayFilterEvent::EMAILER_SEND_PREPARE            => 'emailer_send_prepare',
		ArrayFilterEvent::ENOTIFY                         => 'enotify',
		ArrayFilterEvent::ENOTIFY_MAIL                    => 'enotify_mail',
		ArrayFilterEvent::ENOTIFY_STORE                   => 'enotify_store',
		ArrayFilterEvent::EVENT_CREATED                   => 'event_created',
		ArrayFilterEvent::EVENT_UPDATED                   => 'event_updated',
		ArrayFilterEvent::FEATURE_ENABLED                 => 'isEnabled',
		ArrayFilterEvent::FEATURE_GET                     => 'get',
		ArrayFilterEvent::FETCH_ITEM_BY_LINK              => 'item_by_link',
		ArrayFilterEvent::FOLLOW_CONTACT                  => 'follow',
		ArrayFilterEvent::GENERATE_MAP                    => 'generate_map',
		ArrayFilterEvent::GENERATE_NAMED_MAP              => 'generate_named_map',
		ArrayFilterEvent::GET_SITE_INFO                   => 'getsiteinfo',
		ArrayFilterEvent::GLOBAL_DIR_UPDATE               => 'globaldir_update',
		ArrayFilterEvent::HTML_TO_BBCODE_END              => 'html2bbcode',
		ArrayFilterEvent::INSERT_POST_LOCAL               => 'post_local',
		ArrayFilterEvent::INSERT_POST_LOCAL_END           => 'post_local_end',
		ArrayFilterEvent::INSERT_POST_LOCAL_START         => 'post_local_start',
		ArrayFilterEvent::INSERT_POST_REMOTE              => 'post_remote',
		ArrayFilterEvent::INSERT_POST_REMOTE_END          => 'post_remote_end',
		ArrayFilterEvent::ITEM_PHOTO_MENU                 => 'item_photo_menu',
		ArrayFilterEvent::ITEM_TAGGED                     => 'tagged',
		ArrayFilterEvent::JOT_NETWORKS                    => 'jot_networks',
		LoggedInEvent::NAME                               => 'logged_in',
		LoginFormEvent::NAME                              => 'login_hook',
		MagicAuthSuccessEvent::NAME                       => 'magic_auth_success',
		ArrayFilterEvent::MAP_GET_COORDINATES             => 'Map::getCoordinates',
		ArrayFilterEvent::MODERATION_USERS_TABS           => 'moderation_users_tabs',
		ArrayFilterEvent::NAV_INFO                        => 'nav_info',
		ArrayFilterEvent::NETWORK_CONTENT_START           => 'network_content_init',
		ArrayFilterEvent::NETWORK_CONTENT_TABS            => 'network_tabs',
		ArrayFilterEvent::NETWORK_TO_NAME                 => 'network_to_name',
		ArrayFilterEvent::NOTIFIER_END                    => 'notifier_end',
		ArrayFilterEvent::OCR_DETECTION                   => 'ocr-detection',
		ArrayFilterEvent::OTHER_ENCAPSULATE               => 'other_encapsulate',
		ArrayFilterEvent::OTHER_UNENCAPSULATE             => 'other_unencapsulate',
		ArrayFilterEvent::PAGE_INFO                       => 'page_info_data',
		ArrayFilterEvent::PARSE_LINK                      => 'parse_link',
		ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT      => 'lockview_content',
		ArrayFilterEvent::PHOTO_UPLOAD                    => 'photo_post_file',
		ArrayFilterEvent::PHOTO_UPLOAD_END                => 'photo_post_end',
		ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'photo_upload_form',
		ArrayFilterEvent::PHOTO_UPLOAD_START              => 'photo_post_init',
		ArrayFilterEvent::PREPARE_POST                    => 'prepare_body',
		ArrayFilterEvent::PREPARE_POST_END                => 'prepare_body_final',
		ArrayFilterEvent::PREPARE_POST_FILTER_CONTENT     => 'prepare_body_content_filter',
		ArrayFilterEvent::PREPARE_POST_START              => 'prepare_body_init',
		ArrayFilterEvent::PROBE_DETECT                    => 'probe_detect',
		ArrayFilterEvent::PROFILE_SETTINGS_FORM           => 'profile_edit',
		ArrayFilterEvent::PROFILE_SETTINGS_POST           => 'profile_post',
		ArrayFilterEvent::PROFILE_SIDEBAR                 => 'profile_sidebar',
		ArrayFilterEvent::PROFILE_SIDEBAR_ENTRY           => 'profile_sidebar_enter',
		ArrayFilterEvent::PROFILE_TABS                    => 'profile_tabs',
		ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW        => 'support_follow',
		ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE         => 'support_probe',
		ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW => 'support_revoke_follow',
		ArrayFilterEvent::RENDER_LOCATION                 => 'render_location',
		ArrayFilterEvent::REVOKE_FOLLOW_CONTACT           => 'revoke_follow',
		ArrayFilterEvent::SMILEY_LIST                     => 'smilie',
		ArrayFilterEvent::STORAGE_CONFIG                  => 'storage_config',
		ArrayFilterEvent::STORAGE_INSTANCE                => 'storage_instance',
		ArrayFilterEvent::TEMPLATE_VARS                   => 'template_vars',
		ArrayFilterEvent::UNBLOCK_CONTACT                 => 'unblock',
		ArrayFilterEvent::UNFOLLOW_CONTACT                => 'unfollow',
		ArrayFilterEvent::USER_EXPORT_OPTIONS             => 'uexport_options',
		ZrlInitEvent::NAME                                => 'zrl_init',
		HtmlFilterEvent::HEAD                             => 'head',
		HtmlFilterEvent::FOOTER                           => 'footer',
		HtmlFilterEvent::PAGE_HEADER                      => 'page_header',
		HtmlFilterEvent::PAGE_CONTENT_TOP                 => 'page_content_top',
		HtmlFilterEvent::PAGE_END                         => 'page_end',
		HtmlFilterEvent::MOD_HOME_CONTENT                 => 'home_content',
		HtmlFilterEvent::MOD_ABOUT_CONTENT                => 'about_hook',
		HtmlFilterEvent::MOD_PROFILE_CONTENT              => 'profile_advanced',
		HtmlFilterEvent::JOT_TOOL                         => 'jot_tool',
		HtmlFilterEvent::CONTACT_BLOCK_END                => 'contact_block_end',
	];

	/**
	 * @return array<string, string>
	 */
	public static function getStaticSubscribedEvents(): array
	{
		return [
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
			ArrayFilterEvent::CACHE_ITEM                      => 'onArrayFilterEvent',
			ArrayFilterEvent::CHECK_ITEM_NOTIFICATION         => 'onArrayFilterEvent',
			ArrayFilterEvent::CONNECTOR_SETTINGS_POST         => 'onArrayFilterEvent',
			ArrayFilterEvent::CONTACT_PHOTO_MENU              => 'onArrayFilterEvent',
			ArrayFilterEvent::CONVERSATION_START              => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_STRUCTURE_DEFINITION         => 'onArrayFilterEvent',
			ArrayFilterEvent::DB_VIEW_DEFINITION              => 'onArrayFilterEvent',
			ArrayFilterEvent::DETECT_LANGUAGES                => 'onArrayFilterEvent',
			ArrayFilterEvent::DIRECTORY_ITEM                  => 'onArrayFilterEvent',
			ArrayFilterEvent::DISPLAY_ITEM                    => 'onArrayFilterEvent',
			ArrayFilterEvent::DISPLAY_SETTINGS_POST           => 'onArrayFilterEvent',
			ArrayFilterEvent::EDIT_CONTACT_FORM               => 'onArrayFilterEvent',
			ArrayFilterEvent::EDIT_CONTACT_POST               => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE               => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAIL_GET_MESSAGE_END           => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAILER_SEND                    => 'onArrayFilterEvent',
			ArrayFilterEvent::EMAILER_SEND_PREPARE            => 'onEmailerSendPrepareEvent',
			ArrayFilterEvent::ENOTIFY                         => 'onArrayFilterEvent',
			ArrayFilterEvent::ENOTIFY_MAIL                    => 'onArrayFilterEvent',
			ArrayFilterEvent::ENOTIFY_STORE                   => 'onArrayFilterEvent',
			ArrayFilterEvent::EVENT_CREATED                   => 'onEventCreatedEvent',
			ArrayFilterEvent::EVENT_UPDATED                   => 'onEventUpdatedEvent',
			ArrayFilterEvent::FEATURE_ENABLED                 => 'onArrayFilterEvent',
			ArrayFilterEvent::FEATURE_GET                     => 'onArrayFilterEvent',
			ArrayFilterEvent::FETCH_ITEM_BY_LINK              => 'onArrayFilterEvent',
			ArrayFilterEvent::FOLLOW_CONTACT                  => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_MAP                    => 'onArrayFilterEvent',
			ArrayFilterEvent::GENERATE_NAMED_MAP              => 'onArrayFilterEvent',
			ArrayFilterEvent::GET_SITE_INFO                   => 'onArrayFilterEvent',
			ArrayFilterEvent::GLOBAL_DIR_UPDATE               => 'onArrayFilterEvent',
			ArrayFilterEvent::HTML_TO_BBCODE_END              => 'onHtmlToBbcodeEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL               => 'onInsertPostLocalEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL_END           => 'onInsertPostLocalEndEvent',
			ArrayFilterEvent::INSERT_POST_LOCAL_START         => 'onArrayFilterEvent',
			ArrayFilterEvent::INSERT_POST_REMOTE              => 'onArrayFilterEvent',
			ArrayFilterEvent::INSERT_POST_REMOTE_END          => 'onArrayFilterEvent',
			ArrayFilterEvent::ITEM_PHOTO_MENU                 => 'onArrayFilterEvent',
			ArrayFilterEvent::ITEM_TAGGED                     => 'onArrayFilterEvent',
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
			ArrayFilterEvent::NOTIFIER_END                    => 'onArrayFilterEvent',
			ArrayFilterEvent::OCR_DETECTION                   => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_ENCAPSULATE               => 'onArrayFilterEvent',
			ArrayFilterEvent::OTHER_UNENCAPSULATE             => 'onArrayFilterEvent',
			ArrayFilterEvent::PAGE_INFO                       => 'onArrayFilterEvent',
			ArrayFilterEvent::PARSE_LINK                      => 'onArrayFilterEvent',
			ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT      => 'onPermissionTooltipContentEvent',
			ArrayFilterEvent::PHOTO_UPLOAD                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_END                => 'onPhotoUploadEndEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'onArrayFilterEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_START              => 'onPhotoUploadStartEvent',
			ArrayFilterEvent::PREPARE_POST                    => 'onArrayFilterEvent',
			ArrayFilterEvent::PREPARE_POST_END                => 'onArrayFilterEvent',
			ArrayFilterEvent::PREPARE_POST_FILTER_CONTENT     => 'onArrayFilterEvent',
			ArrayFilterEvent::PREPARE_POST_START              => 'onPreparePostStartEvent',
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
	}

	public static function onNamedEvent(NamedEvent $event): void
	{
		static::callHook($event->getName(), '');
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
	 * Map the INSERT_POST_LOCAL event to `post_local` hook
	 */
	public static function onInsertPostLocalEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$item = $data['item'] ?? [];

		$data['item'] = static::callHook($event->getName(), (array) $item);

		$event->setArray($data);
	}

	/**
	 * Map the INSERT_POST_LOCAL_END event to `post_local_end` hook
	 */
	public static function onInsertPostLocalEndEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$item = $data['item'] ?? [];

		$data['item'] = static::callHook($event->getName(), (array) $item);

		$event->setArray($data);
	}

	/**
	 * Map the PREPARE_POST_START event to `prepare_body_init` hook
	 */
	public static function onPreparePostStartEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$item = $data['item'] ?? [];

		$data['item'] = static::callHook($event->getName(), (array) $item);

		$event->setArray($data);
	}

	/**
	 * Map the PHOTO_UPLOAD_START event to `photo_post_init` hook
	 */
	public static function onPhotoUploadStartEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$request = $data['request'] ?? [];

		$data['request'] = static::callHook($event->getName(), (array) $request);

		$event->setArray($data);
	}

	/**
	 * Map the PHOTO_UPLOAD_END event to `photo_post_end` hook
	 */
	public static function onPhotoUploadEndEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$id = $data['id'] ?? 0;

		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), (int) $id);
	}

	/**
	 * Map the PROFILE_SIDEBAR_ENTRY event to `profile_sidebar_enter` hook
	 */
	public static function onProfileSidebarEntryEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$profile = $data['profile'] ?? [];

		$data['profile'] = static::callHook($event->getName(), (array) $profile);

		$event->setArray($data);
	}

	/**
	 * Map the BBCODE_TO_HTML_START event to `bbcode` hook
	 */
	public static function onBbcodeToHtmlEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$bbcode2html = $data['bbcode2html'] ?? '';

		$data['bbcode2html'] = static::callHook($event->getName(), (string) $bbcode2html);

		$event->setArray($data);
	}

	/**
	 * Map the HTML_TO_BBCODE_END event to `html2bbcode` hook
	 */
	public static function onHtmlToBbcodeEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$html2bbcode = $data['html2bbcode'] ?? '';

		$data['html2bbcode'] = static::callHook($event->getName(), (string) $html2bbcode);

		$event->setArray($data);
	}

	/**
	 * Map the BBCODE_TO_MARKDOWN_END event to `bb2diaspora` hook
	 */
	public static function onBbcodeToMarkdownEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$bbcode2markdown = $data['bbcode2markdown'] ?? '';

		$data['bbcode2markdown'] = static::callHook($event->getName(), (string) $bbcode2markdown);

		$event->setArray($data);
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
