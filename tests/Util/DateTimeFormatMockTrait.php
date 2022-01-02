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

namespace Friendica\Test\Util;

use Friendica\Util\DateTimeFormat;
use Mockery\MockInterface;

trait DateTimeFormatMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Database\DBA
	 */
	private $dtfMock;

	public function mockUtcNow($time, $times = null)
	{
		if (!isset($this->dtfMock)) {
			$this->dtfMock = \Mockery::mock('alias:'. DateTimeFormat::class);
		}

		$this->dtfMock
			->shouldReceive('utcNow')
			->andReturn($time)
			->times($times);
	}

	public function mockUtc($input, $time, $times = null)
	{
		if (!isset($this->dtfMock)) {
			$this->dtfMock = \Mockery::mock('alias:' . DateTimeFormat::class);
		}

		$this->dtfMock
			->shouldReceive('utc')
			->with($input)
			->andReturn($time)
			->times($times);
	}
}
