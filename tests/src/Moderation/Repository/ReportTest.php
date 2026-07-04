<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Moderation\Repository;

use Friendica\Database\Database;
use Friendica\Moderation\Entity;
use Friendica\Moderation\Factory;
use Friendica\Moderation\Repository\Report as ReportRepository;
use Friendica\Test\MockedTestCase;
use Friendica\Util\Clock\FrozenClock;
use Psr\Log\NullLogger;

class ReportTest extends MockedTestCase
{
	private function createRepositoryWithUpdateExpectation(?array $expectedFields, int $reportId = 7): ReportRepository
	{
		$database = $this->getMockBuilder(Database::class)
			->disableOriginalConstructor()
			->onlyMethods(['update'])
			->getMock();

		if ($expectedFields !== null) {
			$database->expects(self::once())
				->method('update')
				->with(
					'report',
					self::callback(function (array $fields) use ($expectedFields) {
						foreach ($expectedFields as $key => $expectedValue) {
							self::assertArrayHasKey($key, $fields);
							self::assertSame($expectedValue, $fields[$key]);
						}

						self::assertArrayHasKey('edited', $fields);
						self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fields['edited']);

						return true;
					}),
					['id' => $reportId],
				)
				->willReturn(true);
		}

		return new ReportRepository(
			$database,
			new NullLogger(),
			new Factory\Report(new NullLogger(), new FrozenClock()),
			new Factory\Report\Post(new NullLogger()),
			new Factory\Report\Rule(new NullLogger()),
		);
	}

	public function testSetStatusUpdatesStatusAndEdited(): void
	{
		$repository = $this->createRepositoryWithUpdateExpectation([
			'status' => Entity\Report::STATUS_CLOSED,
		]);

		self::assertTrue($repository->setStatus(7, Entity\Report::STATUS_CLOSED));
	}

	public function testSetRemarksUpdatesRemarksAndLastEditor(): void
	{
		$repository = $this->createRepositoryWithUpdateExpectation([
			'public-remarks'  => 'Public feedback',
			'private-remarks' => 'Internal note',
			'last-editor-uid' => 23,
		]);

		self::assertTrue($repository->setRemarks(7, 'Public feedback', 'Internal note', 23));
	}

	public function testSetResolutionRejectsInvalidValue(): void
	{
		$repository = $this->createRepositoryWithUpdateExpectation(null);

		self::expectException(\InvalidArgumentException::class);
		self::expectExceptionMessage('Invalid report resolution: 9');

		$repository->setResolution(7, 9);
	}

	public function testUpdateModerationStateAllowsCombinedUpdates(): void
	{
		$repository = $this->createRepositoryWithUpdateExpectation([
			'status'          => Entity\Report::STATUS_OPEN,
			'resolution'      => Entity\Report::RESOLUTION_REJECTED,
			'assigned-uid'    => 17,
			'last-editor-uid' => 42,
		]);

		self::assertTrue($repository->updateModerationState(7, [
			'status'          => Entity\Report::STATUS_OPEN,
			'resolution'      => Entity\Report::RESOLUTION_REJECTED,
			'assigned-uid'    => 17,
			'last-editor-uid' => 42,
		]));
	}
}
