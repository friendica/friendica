<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Service\Addon;

use Friendica\Core\Container;
use Friendica\Service\Addon\AddonContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AddonContainerTest extends TestCase
{
	public function testAddonContainerImplementsContainerInterface(): void
	{
		$container = AddonContainer::fromContainer(
			$this->createStub(Container::class),
			[],
		);

		$this->assertInstanceOf(ContainerInterface::class, $container);
	}

	public function testHasReturnsFalse(): void
	{
		$container = AddonContainer::fromContainer(
			$this->createStub(Container::class),
			[],
		);

		$this->assertFalse($container->has('foo'));
	}

	public function testHasReturnsTrue(): void
	{
		$container = AddonContainer::fromContainer(
			$this->createStub(Container::class),
			['foo'],
		);

		$this->assertTrue($container->has('foo'));
	}

	public function testGetReturnsEntry(): void
	{
		$object = new \stdClass();

		$parent = $this->createMock(Container::class);
		$parent->expects($this->once())->method('create')->with('foo')->willReturn($object);

		$container = AddonContainer::fromContainer(
			$parent,
			['foo'],
		);

		$this->assertSame($object, $container->get('foo'));
	}

	public function testGetThrowsNotFoundExceptionInterface(): void
	{
		$container = AddonContainer::fromContainer(
			$this->createStub(Container::class),
			[],
		);

		$this->expectException(NotFoundExceptionInterface::class);
		$this->expectExceptionMessage('No entry was found for "foo"');

		$container->get('foo');
	}
}
