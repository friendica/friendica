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

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\System;
use Friendica\DI;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function useBaseUrl()
	{
		$baseUrl = \Mockery::mock(BaseURL::class);
		$baseUrl->shouldReceive('getHost')->andReturn('friendica.local')->once();
		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(BaseURL::class)->andReturn($baseUrl);

		DI::init($dice, true);
	}

	private function assertGuid($guid, $length, $prefix = '')
	{
		$length -= strlen($prefix);
		self::assertMatchesRegularExpression("/^" . $prefix . "[a-z0-9]{" . $length . "}?$/", $guid);
	}

	public function testGuidWithoutParameter()
	{
		$this->useBaseUrl();
		$guid = System::createGUID();
		self::assertGuid($guid, 16);
	}

	public function testGuidWithSize32()
	{
		$this->useBaseUrl();
		$guid = System::createGUID(32);
		self::assertGuid($guid, 32);
	}

	public function testGuidWithSize64()
	{
		$this->useBaseUrl();
		$guid = System::createGUID(64);
		self::assertGuid($guid, 64);
	}

	public function testGuidWithPrefix()
	{
		$guid = System::createGUID(23, 'test');
		self::assertGuid($guid, 23, 'test');
	}
}
