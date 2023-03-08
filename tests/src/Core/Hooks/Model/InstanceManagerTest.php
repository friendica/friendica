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

namespace Friendica\Test\src\Core\Hooks\Model;

use Dice\Dice;
use Friendica\Core\Hooks\Model\InstanceManager;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Hooks\InstanceMocks\FakeInstance;
use Friendica\Test\Util\Hooks\InstanceMocks\FakeInstanceDecorator;
use Friendica\Test\Util\Hooks\InstanceMocks\IAmADecoratedInterface;

class InstanceManagerTest extends MockedTest
{
	public function testEqualButNotSameInstance()
	{
		$instance = new InstanceManager(new Dice());

		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake', FakeInstance::class);

		$getInstanceA = $instance->getInstance(IAmADecoratedInterface::class, 'fake');
		$getInstanceB = $instance->getInstance(IAmADecoratedInterface::class, 'fake');

		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
	}

	protected function tearDown(): void
	{
		FakeInstanceDecorator::$countInstance = 0;

		parent::tearDown();
	}

	public function dataTests(): array
	{
		return [
			'only_a' => [
				'aString' => 'test',
			],
			'a_b' => [
				'aString' => 'test',
				'cBool'   => false,
				'bString' => 'test23',

			],
			'a_c' => [
				'aString' => 'test',
				'cBool'   => false,
				'bString' => null,
			],
			'a_b_c' => [
				'aString' => 'test',
				'cBool'   => false,
				'bString' => 'test23',
			],
			'null' => [],
		];
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testInstanceWithConstructorAnonymArgs(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$instance = new InstanceManager(new Dice());

		$args = [];

		if (isset($aString)) {
			$args[] = $aString;
		}
		if (isset($bString)) {
			$args[] = $bString;
		}
		if (isset($cBool)) {
			$args[] = $cBool;
		}

		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake', FakeInstance::class, $args);

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->getInstance(IAmADecoratedInterface::class, 'fake');
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->getInstance(IAmADecoratedInterface::class, 'fake');

		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
		self::assertEquals($aString, $getInstanceA->getAText());
		self::assertEquals($aString, $getInstanceB->getAText());
		self::assertEquals($bString, $getInstanceA->getBText());
		self::assertEquals($bString, $getInstanceB->getBText());
		self::assertEquals($cBool, $getInstanceA->getCBool());
		self::assertEquals($cBool, $getInstanceB->getCBool());
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testInstanceConstructorAndGetInstanceWithNamedArgs(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$instance = new InstanceManager(new Dice());

		$args = [];

		if (isset($aString)) {
			$args[] = $aString;
		}
		if (isset($cBool)) {
			$args[] = $cBool;
		}

		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake', FakeInstance::class, $args);

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->getInstance(IAmADecoratedInterface::class, 'fake', [$bString]);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->getInstance(IAmADecoratedInterface::class, 'fake', [$bString]);

		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
		self::assertEquals($aString, $getInstanceA->getAText());
		self::assertEquals($aString, $getInstanceB->getAText());
		self::assertEquals($bString, $getInstanceA->getBText());
		self::assertEquals($bString, $getInstanceB->getBText());
		self::assertEquals($cBool, $getInstanceA->getCBool());
		self::assertEquals($cBool, $getInstanceB->getCBool());
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testInstanceWithTwoStrategies(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$instance = new InstanceManager(new Dice());

		$args = [];

		if (isset($aString)) {
			$args[] = $aString;
		}
		if (isset($cBool)) {
			$args[] = $cBool;
		}

		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake', FakeInstance::class, $args);
		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake23', FakeInstance::class, $args);

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->getInstance(IAmADecoratedInterface::class, 'fake', [$bString]);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->getInstance(IAmADecoratedInterface::class, 'fake23', [$bString]);

		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
		self::assertEquals($aString, $getInstanceA->getAText());
		self::assertEquals($aString, $getInstanceB->getAText());
		self::assertEquals($bString, $getInstanceA->getBText());
		self::assertEquals($bString, $getInstanceB->getBText());
		self::assertEquals($cBool, $getInstanceA->getCBool());
		self::assertEquals($cBool, $getInstanceB->getCBool());
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testDecorator(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$instance = new InstanceManager(new Dice());

		$args = [];

		if (isset($aString)) {
			$args[] = $aString;
		}
		if (isset($cBool)) {
			$args[] = $cBool;
		}

		$prefix = 'prefix1';

		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake', FakeInstance::class, $args);
		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake23', FakeInstance::class, $args);
		$instance->registerDecorator(IAmADecoratedInterface::class, FakeInstanceDecorator::class, [$prefix]);

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->getInstance(IAmADecoratedInterface::class, 'fake', [$bString]);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->getInstance(IAmADecoratedInterface::class, 'fake23', [$bString]);

		self::assertEquals(2, FakeInstanceDecorator::$countInstance);
		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
		self::assertEquals($prefix . $aString, $getInstanceA->getAText());
		self::assertEquals($prefix . $aString, $getInstanceB->getAText());
		self::assertEquals($prefix . $bString, $getInstanceA->getBText());
		self::assertEquals($prefix . $bString, $getInstanceB->getBText());
		self::assertEquals($prefix . $cBool, $getInstanceA->getCBool());
		self::assertEquals($prefix . $cBool, $getInstanceB->getCBool());
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testTwoDecoratorWithPrefix(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$instance = new InstanceManager(new Dice());

		$args = [];

		if (isset($aString)) {
			$args[] = $aString;
		}
		if (isset($cBool)) {
			$args[] = $cBool;
		}

		$prefix = 'prefix1';

		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake', FakeInstance::class, $args);
		$instance->registerStrategy(IAmADecoratedInterface::class, 'fake23', FakeInstance::class, $args);
		$instance->registerDecorator(IAmADecoratedInterface::class, FakeInstanceDecorator::class, [$prefix]);
		$instance->registerDecorator(IAmADecoratedInterface::class, FakeInstanceDecorator::class);

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->getInstance(IAmADecoratedInterface::class, 'fake', [$bString]);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->getInstance(IAmADecoratedInterface::class, 'fake23', [$bString]);

		self::assertEquals(4, FakeInstanceDecorator::$countInstance);
		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
		self::assertEquals($prefix . $aString, $getInstanceA->getAText());
		self::assertEquals($prefix . $aString, $getInstanceB->getAText());
		self::assertEquals($prefix . $bString, $getInstanceA->getBText());
		self::assertEquals($prefix . $bString, $getInstanceB->getBText());
		self::assertEquals($prefix . $cBool, $getInstanceA->getCBool());
		self::assertEquals($prefix . $cBool, $getInstanceB->getCBool());
	}
}
