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

	public static function getArrayFilterEventData(): array
	{
		return [
			['test', 'test'],
			[ArrayFilterEvent::APP_MENU, 'app_menu'],
			[ArrayFilterEvent::NAV_INFO, 'nav_info'],
			[ArrayFilterEvent::FEATURE_ENABLED, 'isEnabled'],
			[ArrayFilterEvent::FEATURE_GET, 'get'],
			[ArrayFilterEvent::POST_LOCAL_START, 'post_local_start'],
			[ArrayFilterEvent::POST_LOCAL, 'post_local'],
			[ArrayFilterEvent::POST_LOCAL_END, 'post_local_end'],
			[ArrayFilterEvent::PHOTO_UPLOAD_FORM, 'photo_upload_form'],
			[ArrayFilterEvent::NETWORK_TO_NAME, 'network_to_name'],
			[ArrayFilterEvent::CONVERSATION_START, 'conversation_start'],
			[ArrayFilterEvent::DISPLAY_ITEM, 'display_item'],
			[ArrayFilterEvent::RENDER_LOCATION, 'render_location'],
			[ArrayFilterEvent::ITEM_PHOTO_MENU, 'item_photo_menu'],
			[ArrayFilterEvent::PAGE_INFO, 'page_info_data'],
			[ArrayFilterEvent::SMILEY_LIST, 'smilie'],
			[ArrayFilterEvent::JOT_NETWORKS, 'jot_networks'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_FOLLOW, 'support_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_REVOKE_FOLLOW, 'support_revoke_follow'],
			[ArrayFilterEvent::PROTOCOL_SUPPORTS_PROBE, 'support_probe'],
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
