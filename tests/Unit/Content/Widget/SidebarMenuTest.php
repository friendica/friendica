<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content\Widget;

use Friendica\Content\Feature;
use Friendica\Content\Widget\SidebarMenu;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SidebarMenuTest extends TestCase
{
	private const UID = 42;

	private function createMenu(bool $enabled): SidebarMenu
	{
		$pConfig = $this->createStub(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturnCallback(
			fn (int $uid, string $cat, string $key): ?bool => $uid === self::UID && $cat === 'system' && $key === 'widgets_as_menu' ? $enabled : null,
		);

		return new SidebarMenu($this->createStub(L10n::class), $pConfig);
	}

	public function testIsActiveFollowsUserSetting(): void
	{
		self::assertTrue($this->createMenu(true)->isActive(self::UID));
		self::assertFalse($this->createMenu(false)->isActive(self::UID));
	}

	public function testIsNeverActiveForVisitors(): void
	{
		self::assertFalse($this->createMenu(true)->isActive(0));
	}

	/** @return array<string, array{string, bool}> */
	public static function widgets(): array
	{
		return [
			'circles'       => [Feature::CIRCLES, true],
			'groups'        => [Feature::GROUPS, true],
			'channels'      => [Feature::CHANNELS, true],
			'searches'      => [Feature::SEARCHES, true],
			'folders'       => [Feature::FOLDERS, true],
			'trending tags' => [Feature::TRENDING_TAGS, true],
			'archives'      => [Feature::ARCHIVE, false],
			'networks'      => [Feature::NETWORKS, false],
		];
	}

	#[DataProvider('widgets')]
	public function testReplacesWidgetWhenActive(string $feature, bool $replaced): void
	{
		self::assertSame($replaced, $this->createMenu(true)->replaces(self::UID, $feature));
	}

	#[DataProvider('widgets')]
	public function testReplacesNothingWhenInactive(string $feature): void
	{
		self::assertFalse($this->createMenu(false)->replaces(self::UID, $feature));
	}
}
