<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util\Router;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Module\Special\Options;
use Friendica\Test\MockedTestCase;
use Friendica\Util\Router\FriendicaGroupCountBased;

class FriendicaGroupCountBasedTest extends MockedTestCase
{
	public function testOptions(): void
	{
		$collector = new RouteCollector(new Std(), new GroupCountBased());
		$collector->addRoute('GET', '/get', Options::class);
		$collector->addRoute('POST', '/post', Options::class);
		$collector->addRoute('GET', '/multi', Options::class);
		$collector->addRoute('POST', '/multi', Options::class);

		$dispatcher = new FriendicaGroupCountBased($collector->getData());

		self::assertEquals(['GET'], $dispatcher->getOptions('/get'));
		self::assertEquals(['POST'], $dispatcher->getOptions('/post'));
		self::assertEquals(['GET', 'POST'], $dispatcher->getOptions('/multi'));
	}
}
