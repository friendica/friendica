<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Mastodon\Admin;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Factory\Api\Mastodon\Error as ErrorFactory;
use Friendica\Factory\Api\Mastodon\Report as MastodonReportFactory;
use Friendica\Moderation\Factory\Report as ReportFactory;
use Friendica\Moderation\Factory\Report\Post as ReportPostFactory;
use Friendica\Moderation\Factory\Report\Rule as ReportRuleFactory;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Moderation\Repository\Report as ReportRepository;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\Api\Mastodon\Admin\Reports;
use Friendica\Object\Api\Mastodon\Report as MastodonReport;
use Friendica\Util\Profiler;
use Friendica\Test\MockedTestCase;
use Psr\Log\LoggerInterface;

class ReportsTest extends MockedTestCase
{
	public function testGetReportByIdUsesFactoryOutput(): void
	{
		$database           = \Mockery::mock(Database::class);
		$mstdnReportFactory = \Mockery::mock(MastodonReportFactory::class);

		$report           = $this->createReportEntity(123, ReportEntity::CATEGORY_OTHER, 'Single report');
		$mapped           = $this->createMastodonReport(123, 'other');
		$reportRepository = $this->createReportRepository($database, [123 => $report]);

		$database->shouldReceive('selectFirst')
			->with('report', [], ['id' => 123], [])
			->once()
			->andReturn(['id' => 123]);
		$database->shouldReceive('isResult')->with(['id' => 123])->once()->andReturn(true);
		$database->shouldReceive('selectToArray')->with('report-post', ['uri-id', 'status'], ['rid' => 123])->once()->andReturn([]);
		$database->shouldReceive('selectToArray')->with('report-rule', ['line-id', 'text'], ['rid' => 123])->once()->andReturn([]);

		$mstdnReportFactory->shouldReceive('createFromReportEntity')->with($report)->once()->andReturn($mapped);

		$module = $this->createModule(
			$database,
			$reportRepository,
			$mstdnReportFactory,
			['id' => '123'],
		);

		try {
			$module->callGet([]);
			self::fail('Expected JSON exit exception');
		} catch (TestJsonExitException $e) {
			self::assertSame($mapped, $e->payload);
		}
	}

	public function testGetListMapsRowsThroughFactory(): void
	{
		$database           = \Mockery::mock(Database::class);
		$mstdnReportFactory = \Mockery::mock(MastodonReportFactory::class);

		$reportRepository = $this->createReportRepository($database, []);

		$database->shouldReceive('selectToArray')
			->with('report', [], [], ['order' => ['id' => true], 'limit' => 100])
			->once()
			->andReturn([]);

		$mstdnReportFactory->shouldNotReceive('createFromReportEntity');

		$module = $this->createModule($database, $reportRepository, $mstdnReportFactory);

		try {
			$module->callGet([]);
			self::fail('Expected JSON exit exception');
		} catch (TestJsonExitException $e) {
			self::assertSame([], $e->payload);
		}
	}

	private function createModule(Database $database, ReportRepository $reportRepository, MastodonReportFactory $mstdnReportFactory, array $parameters = []): TestableReports
	{
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getCommand')->andReturn('api/v1/admin/reports');

		$logger = \Mockery::mock(LoggerInterface::class);
		$logger->shouldIgnoreMissing();

		return new TestableReports(
			$database,
			$reportRepository,
			$mstdnReportFactory,
			\Mockery::mock(ErrorFactory::class),
			\Mockery::mock(AppHelper::class),
			\Mockery::mock(L10n::class),
			\Mockery::mock(BaseURL::class),
			$args,
			$logger,
			\Mockery::mock(Profiler::class),
			\Mockery::mock(ApiResponse::class),
			[],
			$parameters,
		);
	}

	private function createReportRepository(Database $database, array $reportsById): ReportRepository
	{
		$logger        = \Mockery::mock(LoggerInterface::class);
		$reportFactory = \Mockery::mock(ReportFactory::class);
		$postFactory   = \Mockery::mock(ReportPostFactory::class);
		$ruleFactory   = \Mockery::mock(ReportRuleFactory::class);

		$reportFactory->shouldReceive('createFromTableRow')
			->andReturnUsing(function (array $fields) use ($reportsById): ReportEntity {
				return $reportsById[(int) $fields['id']];
			});

		return new ReportRepository($database, $logger, $reportFactory, $postFactory, $ruleFactory);
	}

	private function createReportEntity(int $id, int $category, string $comment): ReportEntity
	{
		return new ReportEntity(
			reporterCid: 10,
			cid: 20,
			gsid: 30,
			created: new \DateTimeImmutable('2026-01-01 10:00:00', new \DateTimeZone('UTC')),
			category: $category,
			reporterUid: 99,
			comment: $comment,
			id: $id,
		);
	}

	private function createMastodonReport(int $id, string $category): MastodonReport
	{
		return new MastodonReport(
			$id,
			false,
			null,
			$category,
			'',
			false,
			'2026-01-01T10:00:00.000Z',
			'2026-01-01T10:00:00.000Z',
			null,
			null,
			null,
			null,
			[],
			[],
		);
	}
}

class TestJsonExitException extends \RuntimeException
{
	public function __construct(public mixed $payload)
	{
		parent::__construct('JSON exit');
	}
}

class TestableReports extends Reports
{
	public function checkAllowedScope(string $scope) {}

	protected function checkModeratorAccess(): void {}

	public function callGet(array $request = []): void
	{
		$this->get($request);
	}

	public function jsonExit($content, string $content_type = 'application/json; charset=utf-8', int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT): never
	{
		throw new TestJsonExitException($content);
	}
}
