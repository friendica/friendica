<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTest;
use Friendica\Util\DateTimeFormat;

class DateTimeFormatTest extends MockedTest
{
	public function dataYearMonth()
	{
		return [
			'validNormal' => [
				'input' => '1990-10',
				'assert' => true,
			],
			'validOneCharMonth' => [
				'input' => '1990-1',
				'assert' => true,
			],
			'validTwoCharMonth' => [
				'input' => '1990-01',
				'assert' => true,
			],
			'invalidFormat' => [
				'input' => '199-11',
				'assert' => false,
			],
			'invalidFormat2' => [
				'input' => '1990-15',
				'assert' => false,
			],
			'invalidFormat3' => [
				'input' => '99-101',
				'assert' => false,
			],
			'invalidFormat4' => [
				'input' => '11-1990',
				'assert' => false,
			],
			'invalidFuture' => [
				'input' => '3030-12',
				'assert' => false,
			],
			'invalidYear' => [
				'input' => '-100-10',
				'assert' => false,
			],
		];
	}

	/**
	 * @dataProvider dataYearMonth
	 */
	public function testIsYearMonth(string $input, bool $assert)
	{
		$dtFormat = new DateTimeFormat();

		self::assertEquals($assert, $dtFormat->isYearMonth($input));
	}
}
