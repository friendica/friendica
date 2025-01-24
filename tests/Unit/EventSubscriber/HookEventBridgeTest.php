<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\EventSubscriber;

use Friendica\Event\Event;
use Friendica\Event\HtmlFilterEvent;
use Friendica\EventSubscriber\HookEventBridge;
use Friendica\EventSubscriber\StaticEventSubscriber;
use PHPUnit\Framework\TestCase;

class HookEventBridgeTest extends TestCase
{
	public function testCorrectImplementation(): void
	{
		$this->assertTrue(
			is_subclass_of(HookEventBridge::class, StaticEventSubscriber::class, true),
			HookEventBridge::class . ' does not implement ' . StaticEventSubscriber::class
		);
	}

	public function testGetStaticSubscribedEventsReturnsStaticMethods(): void
	{
		$expected = [
			Event::INIT                       => 'onNamedEvent',
			HtmlFilterEvent::HEAD             => 'onHtmlFilterEvent',
			HtmlFilterEvent::FOOTER           => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_CONTENT_TOP => 'onHtmlFilterEvent',
			HtmlFilterEvent::PAGE_END         => 'onHtmlFilterEvent',
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

	public static function getHtmlFilterEventData(): array
	{
		return [
			['test', 'test'],
			[HtmlFilterEvent::HEAD, 'head'],
			[HtmlFilterEvent::FOOTER, 'footer'],
			[HtmlFilterEvent::PAGE_CONTENT_TOP, 'page_content_top'],
			[HtmlFilterEvent::PAGE_END, 'page_end'],
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
