<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\EventSubscriber;

use Friendica\Core\Hook;
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
	 * This allows us to mock the Hook call in tests.
	 *
	 * @var \Closure|null
	 */
	private static $mockedCallHook = null;

	/**
	 * This maps the new event names to the legacy Hook names.
	 */
	private static array $eventMapper = [
		Event::INIT                       => 'init_1',
		ConfigLoadedEvent::CONFIG_LOADED  => 'load_config',
		HtmlFilterEvent::HEAD             => 'head',
		HtmlFilterEvent::FOOTER           => 'footer',
		HtmlFilterEvent::PAGE_CONTENT_TOP => 'page_content_top',
		HtmlFilterEvent::PAGE_END         => 'page_end',
	];

	/**
	 * @return array<string, string>
	 */
	public static function getStaticSubscribedEvents(): array
	{
		return [
			Event::INIT                       => 'onNamedEvent',
			ConfigLoadedEvent::CONFIG_LOADED  => 'onConfigLoadedEvent',
			HtmlFilterEvent::HEAD             => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER           => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END         => 'onHtmlFilterEvent',
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

	public static function onHtmlFilterEvent(HtmlFilterEvent $event): void
	{
		$event->setHtml(
			static::callHook($event->getName(), $event->getHtml())
		);
	}

	/**
	 * @param string|array $data
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
