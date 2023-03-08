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

namespace Friendica\Test\src\Core;

use Friendica\Core\L10n;
use Friendica\Test\MockedTest;

class L10nTest extends MockedTest
{
	public function dataDetectLanguage()
	{
		return [
			'empty'   => [
				'server'  => [],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'en',
			],
			'withGet' => [
				'server'  => [],
				'get'     => ['lang' => 'de'],
				'default' => 'en',
				'assert'  => 'de',
			],
			'withPipe' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'en-gb'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'en-gb',
			],
			'withoutPipe' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'fr',
			],
			'withQuality1' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'de',
			],
			'withQuality2' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de;q=0.2'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'fr',
			],
			'withLangOverride' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de;q=0.2'],
				'get'     => ['lang' => 'it'],
				'default' => 'en',
				'assert'  => 'it',
			],
			'withQualityAndPipe' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de;q=0.2,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
			'withQualityAndInvalid' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,bla;q=0.2,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
			'withQualityAndInvalid2' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'blu;q=0.9,bla;q=0.2,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
			'withQualityAndInvalidAndAbsolute' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'blu;q=0.9,de,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'de',
			],
			'withInvalidGet' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'blu;q=0.9,nb-no;q=0.7'],
				'get'     => ['lang' => 'blu'],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
		];
	}

	/**
	 * @dataProvider dataDetectLanguage
	 */
	public function testDetectLanguage(array $server, array $get, string $default, string $assert)
	{
		self::assertEquals($assert, L10n::detectLanguage($server, $get, $default));
	}
}
