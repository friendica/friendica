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

namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTest;
use Friendica\Util\BasePath;

class BasePathTest extends MockedTest
{
	public function dataPaths()
	{
		return [
			'fullPath' => [
				'server' => [],
				'input' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'relative' => [
				'server' => [],
				'input' => 'config',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'document_root' => [
				'server' => [
					'DOCUMENT_ROOT' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'input' => '/noooop',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'pwd' => [
				'server' => [
					'PWD' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'input' => '/noooop',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'no_overwrite' => [
				'server' => [
					'DOCUMENT_ROOT' => dirname(__DIR__, 3),
					'PWD' => dirname(__DIR__, 3),
				],
				'input' => 'config',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			],
			'no_overwrite_if_invalid' => [
				'server' => [
					'DOCUMENT_ROOT' => '/nopopop',
					'PWD' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
				],
				'input' => '/noatgawe22fafa',
				'output' => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config',
			]
		];
	}

	/**
	 * Test the basepath determination
	 * @dataProvider dataPaths
	 */
	public function testDetermineBasePath(array $server, $input, $output)
	{
		$basepath = new BasePath($input, $server);
		self::assertEquals($output, $basepath->getPath());
	}

	/**
	 * Test the basepath determination with a complete wrong path
	 */
	public function testFailedBasePath()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches("/(.*) is not a valid basepath/");

		$basepath = new BasePath('/now23452sgfgas', []);
		$basepath->getPath();
	}
}
