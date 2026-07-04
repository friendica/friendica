<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Security\TwoFactor\Factory;

use Friendica\Security\TwoFactor\Factory\TrustedBrowser;
use Friendica\Test\MockedTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Psr\Log\NullLogger;

class TrustedBrowserTest extends MockedTestCase
{
	public function testCreateFromTableRowSuccess(): void
	{
		$factory = new TrustedBrowser(new NullLogger());

		$row = [
			'cookie_hash' => Strings::getRandomHex(),
			'uid'         => 42,
			'user_agent'  => 'PHPUnit',
			'created'     => DateTimeFormat::utcNow(),
			'trusted'     => true,
			'last_used'   => null,
		];

		$trustedBrowser = $factory->createFromTableRow($row);

		$this->assertEquals($row, $trustedBrowser->toArray());
	}

	public function testCreateFromTableRowMissingData(): void
	{
		$this->expectException(\TypeError::class);

		$factory = new TrustedBrowser(new NullLogger());

		$row = [
			'cookie_hash' => null,
			'uid'         => null,
			'user_agent'  => null,
			'created'     => null,
			'trusted'     => true,
			'last_used'   => null,
		];

		$trustedBrowser = $factory->createFromTableRow($row);

		$this->assertEquals($row, $trustedBrowser->toArray());
	}

	public function testCreateForUserWithUserAgent(): void
	{
		$factory = new TrustedBrowser(new NullLogger());

		$uid       = 42;
		$userAgent = 'PHPUnit';

		$trustedBrowser = $factory->createForUserWithUserAgent($uid, $userAgent, true);

		$this->assertNotEmpty($trustedBrowser->cookie_hash);
		$this->assertEquals($uid, $trustedBrowser->uid);
		$this->assertEquals($userAgent, $trustedBrowser->user_agent);
		$this->assertTrue($trustedBrowser->trusted);
		$this->assertNotEmpty($trustedBrowser->created);
	}
}
