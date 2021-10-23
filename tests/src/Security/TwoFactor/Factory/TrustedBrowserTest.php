<?php

namespace Friendica\Test\src\Security\TwoFactor\Factory;

use Friendica\Security\TwoFactor\Factory\TrustedBrowser;
use Friendica\Test\MockedTest;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Psr\Log\NullLogger;

class TrustedBrowserTest extends MockedTest
{
	public function testCreateFromTableRowSuccess()
	{
		$factory = new TrustedBrowser(new NullLogger());

		$row = [
			'cookie_hash' => Strings::getRandomHex(),
			'uid' => 42,
			'user_agent' => 'PHPUnit',
			'created' => DateTimeFormat::utcNow(),
			'last_used' => null,
		];

		$trustedBrowser = $factory->createFromTableRow($row);

		$this->assertEquals($row, $trustedBrowser->toArray());
	}

	public function testCreateFromTableRowMissingData()
	{
		$this->expectException(\TypeError::class);

		$factory = new TrustedBrowser(new NullLogger());

		$row = [
			'cookie_hash' => null,
			'uid' => null,
			'user_agent' => null,
			'created' => null,
			'last_used' => null,
		];

		$trustedBrowser = $factory->createFromTableRow($row);

		$this->assertEquals($row, $trustedBrowser->toArray());
	}

	public function testCreateForUserWithUserAgent()
	{
		$factory = new TrustedBrowser(new NullLogger());

		$uid = 42;
		$userAgent = 'PHPUnit';

		$trustedBrowser = $factory->createForUserWithUserAgent($uid, $userAgent);

		$this->assertNotEmpty($trustedBrowser->cookie_hash);
		$this->assertEquals($uid, $trustedBrowser->uid);
		$this->assertEquals($userAgent, $trustedBrowser->user_agent);
		$this->assertNotEmpty($trustedBrowser->created);
	}
}
