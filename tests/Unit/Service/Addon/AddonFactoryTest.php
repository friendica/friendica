<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Service\Addon;

use Friendica\Service\Addon\Addon;
use Friendica\Service\Addon\AddonFactory;
use Friendica\Service\Addon\AddonLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AddonFactoryTest extends TestCase
{
	public function testAddonFactoryImplementsAddonLoaderInterface(): void
	{
		$factory = new AddonFactory(
			dirname(__DIR__, 3) . '/Util',
			$this->createStub(LoggerInterface::class),
		);

		$this->assertInstanceOf(AddonLoader::class, $factory);
	}

	public function testLoadAddonsLoadsTheAddon(): void
	{
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('info')->with('Addon "helloaddon" loaded.');

		$factory = new AddonFactory(
			dirname(__DIR__, 3) . '/Util',
			$logger,
		);

		$addons = $factory->getAddons(['helloaddon' => []]);

		$this->assertArrayHasKey('helloaddon', $addons);
		$this->assertInstanceOf(Addon::class, $addons['helloaddon']);
	}
}
