<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Hooks;

use Friendica\Core\Hook;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\CollectRoutesEvent;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\Event;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Event\NamedEvent;

/**
 * Bridge between the EventDispatcher and the Hook class.
 *
 * @internal Provides BC
 */
final class HookEventBridge
{
	/**
	 * @internal This allows us to mock the Hook call in tests.
	 *
	 * @var \Closure|null
	 */
	private static $mockedCallHook = null;

	/**
	 * This maps the new event names to the legacy Hook names.
	 */
	private static array $eventMapper = [
		Event::INIT                                       => 'init_1',
		Event::HOME_INIT                                  => 'home_init',
		ConfigLoadedEvent::CONFIG_LOADED                  => 'load_config',
		CollectRoutesEvent::COLLECT_ROUTES                => 'route_collection',
		ArrayFilterEvent::APP_MENU                        => 'app_menu',
		ArrayFilterEvent::NAV_INFO                        => 'nav_info',
		ArrayFilterEvent::FEATURE_ENABLED                 => 'isEnabled',
		ArrayFilterEvent::FEATURE_GET                     => 'get',
		ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT      => 'lockview_content',
		ArrayFilterEvent::INSERT_POST_LOCAL_START         => 'post_local_start',
		ArrayFilterEvent::INSERT_POST_LOCAL               => 'post_local',
		ArrayFilterEvent::INSERT_POST_LOCAL_END           => 'post_local_end',
		ArrayFilterEvent::INSERT_POST_REMOTE              => 'post_remote',
		ArrayFilterEvent::INSERT_POST_REMOTE_END          => 'post_remote_end',
		ArrayFilterEvent::PREPARE_POST_START              => 'prepare_body_init',
		ArrayFilterEvent::PREPARE_POST_FILTER_CONTENT     => 'prepare_body_content_filter',
		ArrayFilterEvent::PREPARE_POST                    => 'prepare_body',
		ArrayFilterEvent::PREPARE_POST_END                => 'prepare_body_final',
		ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'photo_upload_form',
		ArrayFilterEvent::PHOTO_UPLOAD_START              => 'photo_post_init',
		ArrayFilterEvent::PHOTO_UPLOAD                    => 'photo_post_file',
		ArrayFilterEvent::PHOTO_UPLOAD_END                => 'photo_post_end',
		ArrayFilterEvent::NETWORK_TO_NAME                 => 'network_to_name',
		ArrayFilterEvent::NETWORK_CONTENT_START           => 'network_content_init',
		ArrayFilterEvent::NETWORK_CONTENT_TABS            => 'network_tabs',
		ArrayFilterEvent::PARSE_LINK                      => 'parse_link',
		ArrayFilterEvent::CONVERSATION_START              => 'conversation_start',
		ArrayFilterEvent::FETCH_ITEM_BY_LINK              => 'item_by_link',
		ArrayFilterEvent::ITEM_TAGGED                     => 'tagged',
		ArrayFilterEvent::DISPLAY_ITEM                    => 'display_item',
		ArrayFilterEvent::CACHE_ITEM                      => 'put_item_in_cache',
		ArrayFilterEvent::CHECK_ITEM_NOTIFICATION         => 'check_item_notification',
		ArrayFilterEvent::ENOTIFY                         => 'enotify',
		ArrayFilterEvent::ENOTIFY_STORE                   => 'enotify_store',
		ArrayFilterEvent::ENOTIFY_MAIL                    => 'enotify_mail',
		ArrayFilterEvent::DETECT_LANGUAGES                => 'detect_languages',
		ArrayFilterEvent::RENDER_LOCATION                 => 'render_location',
		ArrayFilterEvent::ITEM_PHOTO_MENU                 => 'item_photo_menu',
		ArrayFilterEvent::DIRECTORY_ITEM                  => 'directory_item',
		ArrayFilterEvent::CONTACT_PHOTO_MENU              => 'contact_photo_menu',
		ArrayFilterEvent::PROFILE_SIDEBAR_ENTRY           => 'profile_sidebar_enter',
		ArrayFilterEvent::PROFILE_SIDEBAR                 => 'profile_sidebar',
		ArrayFilterEvent::PROFILE_TABS                    => 'profile_tabs',
		ArrayFilterEvent::PROFILE_SETTINGS_FORM           => 'profile_edit',
		ArrayFilterEvent::PROFILE_SETTINGS_POST           => 'profile_post',
		ArrayFilterEvent::MODERATION_USERS_TABS           => 'moderation_users_tabs',
		ArrayFilterEvent::ACL_LOOKUP_END                  => 'acl_lookup_end',
		ArrayFilterEvent::OEMBED_FETCH_END                => 'oembed_fetch_url',
		ArrayFilterEvent::PAGE_INFO                       => 'page_info_data',
		ArrayFilterEvent::SMILEY_LIST                     => 'smilie',
		ArrayFilterEvent::BBCODE_TO_HTML_START            => 'bbcode',
		ArrayFilterEvent::HTML_TO_BBCODE_END              => 'html2bbcode',
		ArrayFilterEvent::BBCODE_TO_MARKDOWN_END          => 'bb2diaspora',
		ArrayFilterEvent::JOT_NETWORKS                    => 'jot_networks',
		ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW        => 'support_follow',
		ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW => 'support_revoke_follow',
		ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE         => 'support_probe',
		ArrayFilterEvent::FOLLOW_CONTACT                  => 'follow',
		ArrayFilterEvent::UNFOLLOW_CONTACT                => 'unfollow',
		ArrayFilterEvent::REVOKE_FOLLOW_CONTACT           => 'revoke_follow',
		ArrayFilterEvent::BLOCK_CONTACT                   => 'block',
		ArrayFilterEvent::UNBLOCK_CONTACT                 => 'unblock',
		ArrayFilterEvent::EDIT_CONTACT_FORM               => 'contact_edit',
		ArrayFilterEvent::EDIT_CONTACT_POST               => 'contact_edit_post',
		ArrayFilterEvent::AVATAR_LOOKUP                   => 'avatar_lookup',
		ArrayFilterEvent::ACCOUNT_AUTHENTICATE            => 'authenticate',
		ArrayFilterEvent::ACCOUNT_REGISTER_FORM           => 'register_form',
		ArrayFilterEvent::ACCOUNT_REGISTER_POST           => 'register_post',
		ArrayFilterEvent::ACCOUNT_REGISTER                => 'register_account',
		ArrayFilterEvent::ACCOUNT_REMOVE                  => 'remove_user',
		ArrayFilterEvent::EVENT_CREATED                   => 'event_created',
		ArrayFilterEvent::EVENT_UPDATED                   => 'event_updated',
		ArrayFilterEvent::ADD_WORKER_TASK                 => 'proc_run',
		ArrayFilterEvent::STORAGE_CONFIG                  => 'storage_config',
		ArrayFilterEvent::STORAGE_INSTANCE                => 'storage_instance',
		ArrayFilterEvent::DB_STRUCTURE_DEFINITION         => 'dbstructure_definition',
		ArrayFilterEvent::DB_VIEW_DEFINITION              => 'dbview_definition',
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
			static::callHook($event->getName(), $event->getRouteCollector())
		);
	}

	/**
	 * Map the PERMISSION_TOOLTIP_CONTENT event to `lockview_content` hook
	 */
	public static function onPermissionTooltipContentEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$model = (array) $data['model'] ?? [];

		$data['model'] = static::callHook($event->getName(), $model);

		$event->setArray($data);
	}

	/**
	 * Map the INSERT_POST_LOCAL event to `post_local` hook
	 */
	public static function onInsertPostLocalEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$item = (array) $data['item'] ?? [];

		$data['item'] = static::callHook($event->getName(), $item);

		$event->setArray($data);
	}

	/**
	 * Map the INSERT_POST_LOCAL_END event to `post_local_end` hook
	 */
	public static function onInsertPostLocalEndEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$item = (array) $data['item'] ?? [];

		$data['item'] = static::callHook($event->getName(), $item);

		$event->setArray($data);
	}

	/**
	 * Map the PREPARE_POST_START event to `prepare_body_init` hook
	 */
	public static function onPreparePostStartEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$item = (array) $data['item'] ?? [];

		$data['item'] = static::callHook($event->getName(), $item);

		$event->setArray($data);
	}

	/**
	 * Map the PHOTO_UPLOAD_START event to `photo_post_init` hook
	 */
	public static function onPhotoUploadStartEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$request = (array) $data['request'] ?? [];

		$data['request'] = static::callHook($event->getName(), $request);

		$event->setArray($data);
	}

	/**
	 * Map the PHOTO_UPLOAD_END event to `photo_post_end` hook
	 */
	public static function onPhotoUploadEndEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$id = (int) $data['id'] ?? 0;

		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), $id);
	}

	/**
	 * Map the PROFILE_SIDEBAR_ENTRY event to `profile_sidebar_enter` hook
	 */
	public static function onProfileSidebarEntryEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$profile = (array) $data['profile'] ?? [];

		$data['profile'] = static::callHook($event->getName(), $profile);

		$event->setArray($data);
	}

	/**
	 * Map the OEMBED_FETCH_END event to `oembed_fetch_url` hook
	 */
	public static function onOembedFetchEndEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$url = (string) $data['url'] ?? '';

		$data['url'] = static::callHook($event->getName(), $url);

		$event->setArray($data);
	}

	/**
	 * Map the BBCODE_TO_HTML_START event to `bbcode` hook
	 */
	public static function onBbcodeToHtmlEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$bbcode2html = (string) $data['bbcode2html'] ?? '';

		$data['bbcode2html'] = static::callHook($event->getName(), $bbcode2html);

		$event->setArray($data);
	}

	/**
	 * Map the HTML_TO_BBCODE_END event to `html2bbcode` hook
	 */
	public static function onHtmlToBbcodeEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$html2bbcode = (string) $data['html2bbcode'] ?? '';

		$data['html2bbcode'] = static::callHook($event->getName(), $html2bbcode);

		$event->setArray($data);
	}

	/**
	 * Map the BBCODE_TO_MARKDOWN_END event to `bb2diaspora` hook
	 */
	public static function onBbcodeToMarkdownEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$bbcode2markdown = (string) $data['bbcode2markdown'] ?? '';

		$data['bbcode2markdown'] = static::callHook($event->getName(), $bbcode2markdown);

		$event->setArray($data);
	}

	/**
	 * Map the ACCOUNT_REGISTER event to `register_account` hook
	 */
	public static function onAccountRegisterEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$uid = (int) $data['uid'] ?? 0;

		$data['uid'] = static::callHook($event->getName(), $uid);

		$event->setArray($data);
	}

	/**
	 * Map the ACCOUNT_REMOVE event to `remove_account` hook
	 */
	public static function onAccountRemoveEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$user = (array) $data['user'] ?? [];

		$data['user'] = static::callHook($event->getName(), $user);

		$event->setArray($data);
	}

	/**
	 * Map the EVENT_CREATED event to `event_created` hook
	 */
	public static function onEventCreatedEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$id = (int) $data['event']['id'] ?? 0;

		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), $id);
	}

	/**
	 * Map the EVENT_UPDATED event to `event_updated` hook
	 */
	public static function onEventUpdatedEvent(ArrayFilterEvent $event): void
	{
		$data = $event->getArray();

		$id = (int) $data['event']['id'] ?? 0;

		// one-way-event: we don't care about the returned value
		static::callHook($event->getName(), $id);
	}

	public static function onArrayFilterEvent(ArrayFilterEvent $event): void
	{
		$event->setArray(
			static::callHook($event->getName(), $event->getArray())
		);
	}

	public static function onHtmlFilterEvent(HtmlFilterEvent $event): void
	{
		$event->setHtml(
			static::callHook($event->getName(), $event->getHtml())
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
