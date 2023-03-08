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

namespace Friendica\Test\src;

use Friendica\Test\MockedTest;
use Friendica\Test\Util\CollectionDouble;
use Friendica\Test\Util\EntityDouble;

class CollectionTest extends MockedTest
{
	/**
	 * Test if the BaseCollection::column() works as expected
	 */
	public function testGetArrayCopy()
	{
		$collection = new CollectionDouble();
		$collection->append(new EntityDouble('test', 23, new \DateTime('now', new \DateTimeZone('UTC')), 'privTest'));
		$collection->append(new EntityDouble('test2', 25, new \DateTime('now', new \DateTimeZone('UTC')), 'privTest23'));

		self::assertEquals(['test', 'test2'], $collection->column('protString'));
		self::assertEmpty($collection->column('privString'));
		self::assertEquals([23,25], $collection->column('protInt'));
	}
}
