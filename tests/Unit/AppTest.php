<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit;

use Friendica\App;
use Friendica\Core\Container;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	public function testFromContainerReturnsApp(): void
	{
		$container = $this->createMock(Container::class);
		$container->expects($this->never())->method('create');

		$app = App::fromContainer($container);

		$this->assertInstanceOf(App::class, $app); // @phpstan-ignore method.alreadyNarrowedType
	}
}
