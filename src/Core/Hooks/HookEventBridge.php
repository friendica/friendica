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
		ArrayFilterEvent::POST_LOCAL_START                => 'post_local_start',
		ArrayFilterEvent::POST_LOCAL                      => 'post_local',
		ArrayFilterEvent::POST_LOCAL_END                  => 'post_local_end',
		ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'photo_upload_form',
		ArrayFilterEvent::NETWORK_TO_NAME                 => 'network_to_name',
		ArrayFilterEvent::CONVERSATION_START              => 'conversation_start',
		ArrayFilterEvent::DISPLAY_ITEM                    => 'display_item',
		ArrayFilterEvent::RENDER_LOCATION                 => 'render_location',
		ArrayFilterEvent::ITEM_PHOTO_MENU                 => 'item_photo_menu',
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
		HtmlFilterEvent::HEAD                             => 'head',
		HtmlFilterEvent::FOOTER                           => 'footer',
		HtmlFilterEvent::PAGE_HEADER                      => 'page_header',
		HtmlFilterEvent::PAGE_CONTENT_TOP                 => 'page_content_top',
		HtmlFilterEvent::PAGE_END                         => 'page_end',
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
			ArrayFilterEvent::POST_LOCAL_START                => 'onArrayFilterEvent',
			ArrayFilterEvent::POST_LOCAL                      => 'onArrayFilterEvent',
			ArrayFilterEvent::POST_LOCAL_END                  => 'onArrayFilterEvent',
			ArrayFilterEvent::PHOTO_UPLOAD_FORM               => 'onArrayFilterEvent',
			ArrayFilterEvent::NETWORK_TO_NAME                 => 'onArrayFilterEvent',
			ArrayFilterEvent::CONVERSATION_START              => 'onArrayFilterEvent',
			ArrayFilterEvent::DISPLAY_ITEM                    => 'onArrayFilterEvent',
			ArrayFilterEvent::RENDER_LOCATION                 => 'onArrayFilterEvent',
			ArrayFilterEvent::ITEM_PHOTO_MENU                 => 'onArrayFilterEvent',
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
			HtmlFilterEvent::HEAD                             => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER                           => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_HEADER                      => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP                 => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END                         => 'onHtmlFilterEvent',
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
	 * @param string|array|object $data
	 *
	 * @return string|array|object
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
