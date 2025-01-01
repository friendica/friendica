<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit;

use Dice\Dice;
use Friendica\App;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	public function testFromDiceReturnsApp(): void
	{
		$dice = $this->createMock(Dice::class);
		$dice->expects($this->never())->method('create');

		$app = App::fromDice($dice);

		$this->assertInstanceOf(App::class, $app);
	}
}
