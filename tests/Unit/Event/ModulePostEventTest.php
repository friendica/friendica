<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\ModulePostEvent;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class ModulePostEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ModulePostEvent('test', 'moduleName', \stdClass::class, []);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public static function getPublicConstants(): array
	{
		return [
			[ModulePostEvent::MODULE_POST, 'friendica.module_post'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ModulePostEvent('test', 'moduleName', \stdClass::class, []);

		$this->assertSame('test', $event->getName());
	}

	public function testGetModuleNameReturnsModuleName(): void
	{
		$event = new ModulePostEvent('test', 'moduleName', \stdClass::class, []);

		$this->assertSame('moduleName', $event->getModuleName());
	}

	public function testGetModuleClassReturnsModuleClass(): void
	{
		$event = new ModulePostEvent('test', 'moduleName', \stdClass::class, []);

		$this->assertSame(\stdClass::class, $event->getModuleClass());
	}

	public function testGetPostReturnsCorrectArray(): void
	{
		$post = ['key' => 'value'];

		$event = new ModulePostEvent('test', 'moduleName', \stdClass::class, $post);

		$this->assertSame($post, $event->getPost());
	}

	public function testSetPostUpdatesPost(): void
	{
		$event = new ModulePostEvent('test', 'moduleName', \stdClass::class, ['key' => 'value']);

		$newPost = ['key2' => 'value2'];

		$event->setPost($newPost);

		$this->assertSame($newPost, $event->getPost());
	}
}
