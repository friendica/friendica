<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Security\TwoFactor\Model;

use Friendica\Security\TwoFactor\Model\TrustedBrowser;
use Friendica\Test\MockedTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class TrustedBrowserTest extends MockedTestCase
{
	public function test__construct(): void
	{
		$hash = Strings::getRandomHex();

		$trustedBrowser = new TrustedBrowser(
			$hash,
			42,
			'PHPUnit',
			true,
			DateTimeFormat::utcNow(),
		);

		$this->assertEquals($hash, $trustedBrowser->cookie_hash);
		$this->assertEquals(42, $trustedBrowser->uid);
		$this->assertEquals('PHPUnit', $trustedBrowser->user_agent);
		$this->assertTrue($trustedBrowser->trusted);
		$this->assertNotEmpty($trustedBrowser->created);
	}

	public function testRecordUse(): void
	{
		$hash = Strings::getRandomHex();
		$past = DateTimeFormat::utc('now - 5 minutes');

		$trustedBrowser = new TrustedBrowser(
			$hash,
			42,
			'PHPUnit',
			true,
			$past,
			$past,
		);

		$trustedBrowser->recordUse();

		$this->assertEquals($past, $trustedBrowser->created);
		$this->assertGreaterThan($past, $trustedBrowser->last_used);
	}
}
