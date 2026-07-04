<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content;

use Friendica\App\BaseURL;
use Friendica\Content\Item;
use Friendica\Core\L10n;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Protocol\Activity;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Content\Post\Factory\PostMedia as PostMediaFactory;
use Friendica\Content\Post\Repository\PostMedia as PostMediaRepository;
use Friendica\Content\Text\BBCode\Video;
use Friendica\Test\MockedTestCase;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Emailer;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class ItemTest extends MockedTestCase
{
	/** @var object|null */
	private $originalDice;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalDice = $this->getDiceProperty();
		$this->setDiceProfilerMock();
	}

	protected function tearDown(): void
	{
		$this->restoreDice();
		parent::tearDown();
	}

	private function getDiceProperty()
	{
		$reflection = new \ReflectionClass(\Friendica\DI::class);
		$property   = $reflection->getProperty('dice');

		return $property->getValue();
	}

	private function setDiceProperty($value): void
	{
		$reflection = new \ReflectionClass(\Friendica\DI::class);
		$property   = $reflection->getProperty('dice');
		$property->setValue(null, $value);
	}

	private function setDiceProfilerMock(): void
	{
		$profiler = $this->createMock(Profiler::class);
		$profiler->expects(self::any())->method('startRecording');
		$profiler->expects(self::any())->method('stopRecording');

		$eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$eventDispatcher->expects(self::any())
			->method('dispatch')
			->willReturnArgument(0);

		$config = $this->createMock(IManageConfigValues::class);
		$config->expects(self::any())
			->method('get')
			->willReturnMap([
				['system', 'profiler', null, false],
				['rendertime', 'callstack', null, false],
			]);

		$l10n    = $this->createMock(L10n::class);
		$baseUrl = $this->createMock(BaseURL::class);

		$mockFactory = \Closure::bind(function ($class) {
			return $this->createMock($class);
		}, $this, self::class);

		$mockDice = new ItemTestDice($profiler, $eventDispatcher, $config, $l10n, $baseUrl, $mockFactory);

		$this->setDiceProperty($mockDice);
	}

	private function restoreDice(): void
	{
		$this->setDiceProperty($this->originalDice);
	}

	public static function dataRedundantSummary()
	{
		return [
			'empty-summary' => [
				'expected' => false,
				'body'     => 'Some content here',
				'summary'  => '',
			],
			'identical-summary' => [
				'expected' => true,
				'body'     => 'Some content here',
				'summary'  => 'Some content here',
			],
			'same-start-different-case' => [
				'expected' => true,
				'body'     => 'Some content here',
				'summary'  => 'some CONTENT here',
			],
			'prefix-match' => [
				'expected' => true,
				'body'     => 'Some content here with extra text',
				'summary'  => 'Some content here',
			],
			'different-summary' => [
				'expected' => false,
				'body'     => 'Some content here',
				'summary'  => 'Completely different summary',
			],
			'summary-longer-than-body' => [
				'expected' => false,
				'body'     => 'Short',
				'summary'  => 'Short but longer summary',
			],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $body
	 * @param string $summary
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataRedundantSummary')]
	public function testRedundantSummary(bool $expected, string $body, string $summary): void
	{
		$item = $this->createItem();

		self::assertSame($expected, $item->redundantSummary($body, $summary));
	}

	private function createItem(): Item
	{
		return new Item(
			$this->createStub(LoggerInterface::class),
			$this->createStub(Profiler::class),
			new Activity(),
			$this->createStub(L10n::class),
			$this->createStub(IHandleUserSessions::class),
			new Video(),
			new ACLFormatter(),
			$this->createStub(IManagePersonalConfigValues::class),
			$this->createStub(IManageConfigValues::class),
			$this->createStub(BaseURL::class),
			$this->createStub(Emailer::class),
			$this->createStub(EventDispatcherInterface::class),
			$this->createStub(PostMediaRepository::class),
			$this->createStub(PostMediaFactory::class),
		);
	}
}
