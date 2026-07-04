<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Navigation\Notifications\Entity;

use Friendica\Navigation\Notifications\Entity\Notify;
use Friendica\Test\FixtureTestCase;

class NotifyTest extends FixtureTestCase
{
	public static function dataFormatNotify(): array
	{
		return [
			'xss-notify' => [
				'name'      => 'Whiskers',
				'message'   => '{0} commented in the thread "If my username causes a pop up in a piece of software, that softwar…" from <script>alert("Tek");</script>',
				'assertion' => '<span class="contactname">Whiskers</span> commented in the thread &quot;If my username causes a pop up in a piece of software, that softwar…&quot; from &lt;script&gt;alert(&quot;Tek&quot;);&lt;/script&gt;',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataFormatNotify')]
	public function testFormatNotify(string $name, string $message, string $assertion): void
	{
		self::assertEquals($assertion, Notify::formatMessage($name, $message));
	}
}
