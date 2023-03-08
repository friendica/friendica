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

namespace Friendica\Test\src\Security\TwoFactor\Model;

use Friendica\Security\TwoFactor\Model\TrustedBrowser;
use Friendica\Test\MockedTest;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class TrustedBrowserTest extends MockedTest
{
	public function test__construct()
	{
		$hash = Strings::getRandomHex();

		$trustedBrowser = new TrustedBrowser(
			$hash,
			42,
			'PHPUnit',
			true,
			DateTimeFormat::utcNow()
		);

		$this->assertEquals($hash, $trustedBrowser->cookie_hash);
		$this->assertEquals(42, $trustedBrowser->uid);
		$this->assertEquals('PHPUnit', $trustedBrowser->user_agent);
		$this->assertTrue($trustedBrowser->trusted);
		$this->assertNotEmpty($trustedBrowser->created);
	}

	public function testRecordUse()
	{
		$hash = Strings::getRandomHex();
		$past = DateTimeFormat::utc('now - 5 minutes');

		$trustedBrowser = new TrustedBrowser(
			$hash,
			42,
			'PHPUnit',
			true,
			$past,
			$past
		);

		$trustedBrowser->recordUse();

		$this->assertEquals($past, $trustedBrowser->created);
		$this->assertGreaterThan($past, $trustedBrowser->last_used);
	}
}
