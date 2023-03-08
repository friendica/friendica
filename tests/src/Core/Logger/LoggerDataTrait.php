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

namespace Friendica\Test\src\Core\Logger;

trait LoggerDataTrait
{
	public function dataTests()
	{
		return [
			'emergency' => [
				'function' => 'emergency',
				'message' => 'test',
				'context' => ['a' => 'context'],
			],
			'alert' => [
				'function' => 'alert',
				'message' => 'test {test}',
				'context' => ['a' => 'context', 2 => 'so', 'test' => 'works'],
			],
			'critical' => [
				'function' => 'critical',
				'message' => 'test crit 2345',
				'context' => ['a' => 'context', 'wit' => ['more', 'array']],
			],
			'error' => [
				'function' => 'error',
				'message' => 2.554,
				'context' => [],
			],
			'warning' => [
				'function' => 'warning',
				'message' => 'test warn',
				'context' => ['a' => 'context'],
			],
			'notice' => [
				'function' => 'notice',
				'message' => 2346,
				'context' => ['a' => 'context'],
			],
			'info' => [
				'function' => 'info',
				'message' => null,
				'context' => ['a' => 'context'],
			],
			'debug' => [
				'function' => 'debug',
				'message' => true,
				'context' => ['a' => false],
			],
		];
	}
}
