<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core;

use Friendica\Core\Container;
use Friendica\Core\DiceContainer;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DiceContainerTest extends TestCase
{
	public function testFromBasePathReturnsContainer(): void
	{
		$root = vfsStream::setup('friendica', null, [
			'static' => [
				'dependencies.config.php' => '<?php return [];',
			],
		]);

		$container = DiceContainer::fromBasePath($root->url());

		$this->assertInstanceOf(Container::class, $container); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testCreateReturnsObject(): void
	{
		$root = vfsStream::setup('friendica', null, [
			'static' => [
				'dependencies.config.php' => <<< PHP
					<?php return [
						\Psr\Log\LoggerInterface::class => [
							'instanceOf' => \Psr\Log\NullLogger::class,
						],
					];
					PHP,
			],
		]);

		$container = DiceContainer::fromBasePath($root->url());

		$this->assertInstanceOf(NullLogger::class, $container->create(LoggerInterface::class));
	}
}
