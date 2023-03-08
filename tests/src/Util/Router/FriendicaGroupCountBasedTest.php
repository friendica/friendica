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

namespace Friendica\Test\src\Util\Router;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Module\Special\Options;
use Friendica\Test\MockedTest;
use Friendica\Util\Router\FriendicaGroupCountBased;

class FriendicaGroupCountBasedTest extends MockedTest
{
	public function testOptions()
	{
		$collector = new RouteCollector(new Std(), new GroupCountBased());
		$collector->addRoute('GET', '/get', Options::class);
		$collector->addRoute('POST', '/post', Options::class);
		$collector->addRoute('GET', '/multi', Options::class);
		$collector->addRoute('POST', '/multi', Options::class);

		$dispatcher = new FriendicaGroupCountBased($collector->getData());

		self::assertEquals(['GET'], $dispatcher->getOptions('/get'));
		self::assertEquals(['POST'], $dispatcher->getOptions('/post'));
		self::assertEquals(['GET', 'POST'], $dispatcher->getOptions('/multi'));
	}
}
