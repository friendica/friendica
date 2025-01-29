<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class ConfigLoadedEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ConfigLoadedEvent('test', $this->createStub(ConfigFileManager::class));

		$this->assertInstanceOf(NamedEvent::class, $event);
	}

	public static function getPublicConstants(): array
	{
		return [
			[ConfigLoadedEvent::CONFIG_LOADED, 'friendica.config_loaded'],
		];
	}

	/**
	 * @dataProvider getPublicConstants
	 */
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ConfigLoadedEvent('test', $this->createStub(ConfigFileManager::class));

		$this->assertSame('test', $event->getName());
	}

	public function testGetConfigReturnsCorrectString(): void
	{
		$config = $this->createStub(ConfigFileManager::class);

		$event = new ConfigLoadedEvent('test', $config);

		$this->assertSame($config, $event->getConfig());
	}
}
