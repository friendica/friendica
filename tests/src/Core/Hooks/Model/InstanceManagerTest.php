<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Hooks\Model;

use Dice\Dice;
use Friendica\Core\Hooks\Exceptions\HookInstanceException;
use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;
use Friendica\Core\Hooks\Model\DiceInstanceManager;
use Friendica\Core\Hooks\Util\StrategiesFileManager;
use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\Hooks\InstanceMocks\FakeInstance;
use Friendica\Test\Util\Hooks\InstanceMocks\FakeInstanceDecorator;
use Friendica\Test\Util\Hooks\InstanceMocks\IAmADecoratedInterface;
use Mockery\MockInterface;

class InstanceManagerTest extends MockedTestCase
{
	/** @var StrategiesFileManager|MockInterface */
	protected $hookFileManager;

	protected function setUp(): void
	{
		parent::setUp();

		$this->hookFileManager = \Mockery::mock(StrategiesFileManager::class);
		$this->hookFileManager->shouldReceive('setupStrategies')->withAnyArgs();
	}

	protected function tearDown(): void
	{
		FakeInstanceDecorator::$countInstance = 0;

		parent::tearDown();
	}

	public function testEqualButNotSameInstance()
	{
		$instance = new DiceInstanceManager(new Dice(), $this->hookFileManager);

		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');

		$getInstanceA = $instance->create(IAmADecoratedInterface::class, 'fake');
		$getInstanceB = $instance->create(IAmADecoratedInterface::class, 'fake');

		self::assertEquals($getInstanceA, $getInstanceB);
		self::assertNotSame($getInstanceA, $getInstanceB);
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
	public function testInstanceWithArgs(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$instance = new DiceInstanceManager(new Dice(), $this->hookFileManager);

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

		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->create(IAmADecoratedInterface::class, 'fake', $args);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->create(IAmADecoratedInterface::class, 'fake', $args);

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
		$instance = new DiceInstanceManager(new Dice(), $this->hookFileManager);

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

		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');
		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake23');

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->create(IAmADecoratedInterface::class, 'fake', $args);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->create(IAmADecoratedInterface::class, 'fake23', $args);

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
	 * Test the exception in case the interface was already registered
	 */
	public function testDoubleRegister()
	{
		self::expectException(HookRegisterArgumentException::class);
		self::expectExceptionMessage(sprintf('A class with the name %s is already set for the interface %s', 'fake', IAmADecoratedInterface::class));

		$instance = new DiceInstanceManager(new Dice(), $this->hookFileManager);
		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');
		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');
	}

	/**
	 * Test the exception in case the name of the instance isn't registered
	 */
	public function testWrongInstanceName()
	{
		self::expectException(HookInstanceException::class);
		self::expectExceptionMessage(sprintf('The class with the name %s isn\'t registered for the class or interface %s', 'fake', IAmADecoratedInterface::class));

		$instance = new DiceInstanceManager(new Dice(), $this->hookFileManager);
		$instance->create(IAmADecoratedInterface::class, 'fake');
	}

	/**
	 * Test in case there are already some rules
	 *
	 * @dataProvider dataTests
	 */
	public function testWithGivenRules(string $aString = null, bool $cBool = null, string $bString = null)
	{
		$args = [];

		if (isset($aString)) {
			$args[] = $aString;
		}
		if (isset($bString)) {
			$args[] = $bString;
		}

		$dice = (new Dice())->addRules([
			FakeInstance::class => [
				'constructParams' => $args,
			],
		]);

		$args = [];

		if (isset($cBool)) {
			$args[] = $cBool;
		}

		$instance = new DiceInstanceManager($dice, $this->hookFileManager);

		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');

		/** @var IAmADecoratedInterface $getInstanceA */
		$getInstanceA = $instance->create(IAmADecoratedInterface::class, 'fake', $args);
		/** @var IAmADecoratedInterface $getInstanceB */
		$getInstanceB = $instance->create(IAmADecoratedInterface::class, 'fake', $args);

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
	 * @see https://github.com/friendica/friendica/issues/13318
	 */
	public function testCaseInsensitiveNames()
	{
		$instance = new DiceInstanceManager(new Dice(), $this->hookFileManager);

		$instance->registerStrategy(IAmADecoratedInterface::class, FakeInstance::class, 'fake');

		// CamelCase
		self::assertInstanceOf(FakeInstance::class, $instance->create(IAmADecoratedInterface::class, 'Fake'));
		// UPPER CASE
		self::assertInstanceOf(FakeInstance::class, $instance->create(IAmADecoratedInterface::class, 'FAKE'));
		// lower case
		self::assertInstanceOf(FakeInstance::class, $instance->create(IAmADecoratedInterface::class, 'fake'));
		// UnKnOwN
		self::assertInstanceOf(FakeInstance::class, $instance->create(IAmADecoratedInterface::class, 'fAkE'));
	}

}
