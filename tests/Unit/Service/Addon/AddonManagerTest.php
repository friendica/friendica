<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Service\Addon;

use Friendica\Event\HtmlFilterEvent;
use Friendica\Service\Addon\Addon;
use Friendica\Service\Addon\AddonLoader;
use Friendica\Service\Addon\AddonManager;
use PHPUnit\Framework\TestCase;

class AddonManagerTest extends TestCase
{
	public function testBootstrapAddonsLoadsTheAddon(): void
	{
		$loader = $this->createMock(AddonLoader::class);
		$loader->expects($this->once())->method('getAddons')->willReturn(['helloaddon' => $this->createMock(Addon::class)]);

		$manager = new AddonManager($loader);

		$manager->bootstrapAddons(['helloaddon' => []]);
	}

	public function testGetAllSubscribedEventsReturnsEvents(): void
	{
		$addon = $this->createStub(Addon::class);
		$addon->method('getSubscribedEvents')->willReturn([[HtmlFilterEvent::PAGE_END, [Addon::class, 'onPageEnd']]]);

		$loader = $this->createStub(AddonLoader::class);
		$loader->method('getAddons')->willReturn(['helloaddon' => $addon]);

		$manager = new AddonManager($loader);

		$manager->bootstrapAddons(['helloaddon' => []]);

		$this->assertSame(
			[[HtmlFilterEvent::PAGE_END, [Addon::class, 'onPageEnd']]],
			$manager->getAllSubscribedEvents(),
		);
	}

	public function testGetRequiredDependenciesReturnsArray(): void
	{
		$addon = $this->createStub(Addon::class);
		$addon->method('getId')->willReturn('helloaddon');
		$addon->method('getRequiredDependencies')->willReturn(['foo', 'bar']);

		$loader = $this->createStub(AddonLoader::class);
		$loader->method('getAddons')->willReturn(['helloaddon' => $addon]);

		$manager = new AddonManager($loader);

		$manager->bootstrapAddons(['helloaddon' => []]);

		$this->assertSame(
			['helloaddon' => ['foo', 'bar']],
			$manager->getRequiredDependencies(),
		);
	}
}
