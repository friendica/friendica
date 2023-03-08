<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
			'trusted' => true,
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
			'trusted' => true,
			'last_used' => null,
		];

		$trustedBrowser = $factory->createFromTableRow($row);

		$this->assertEquals($row, $trustedBrowser->toArray());
	}

	public function testCreateForUserWithUserAgent()
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
