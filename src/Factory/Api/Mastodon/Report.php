<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Object\Api\Mastodon\Report as MstdnReport;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class Report extends BaseFactory
{
	public function __construct(
		LoggerInterface $logger,
		private readonly Database $database,
		private readonly Account $mstdnAccountFactory,
		private readonly Status $mstdnStatusFactory,
	) {
		parent::__construct($logger);
	}

	public function createFromReportEntity(ReportEntity $report): MstdnReport
	{
		$createdAt   = $report->created->format(DateTimeFormat::JSON);
		$updatedAt   = $report->edited ? $report->edited->format(DateTimeFormat::JSON) : $createdAt;
		$actionTaken = $report->status === ReportEntity::STATUS_CLOSED;
		$reporterUid = (int) $report->reporterUid;

		return new MstdnReport(
			$report->id,
			$actionTaken,
			$actionTaken ? $updatedAt : null,
			$this->idToCategory($report->category),
			$report->comment,
			$report->forward,
			$createdAt,
			$updatedAt,
			$this->buildAccount($report->reporterCid, $reporterUid),
			$this->buildAccount($report->cid, $reporterUid),
			$report->assignedUid ? $this->buildAccountByUserId($report->assignedUid) : null,
			$report->lastEditorUid ? $this->buildAccountByUserId($report->lastEditorUid) : null,
			$this->buildStatuses($report->id, $reporterUid),
			$this->buildRules($report->id),
		);
	}

	private function buildAccount(int $contactId, int $uid): ?array
	{
		try {
			return $this->mstdnAccountFactory->createFromContactId($contactId, $uid)->toArray();
		} catch (\Throwable) {
			return null;
		}
	}

	private function buildAccountByUserId(int $userId): ?array
	{
		$contactId = Contact::getPublicIdByUserId($userId);
		if (!$contactId) {
			return null;
		}

		return $this->buildAccount($contactId, $userId);
	}

	private function buildStatuses(int $reportId, int $uid): array
	{
		$rows = $this->database->selectToArray('report-post', ['uri-id'], ['rid' => $reportId]);

		$statuses = [];
		foreach ($rows as $row) {
			try {
				$statuses[] = $this->mstdnStatusFactory->createFromUriId((int) $row['uri-id'], $uid)->toArray();
			} catch (\Throwable) {
				continue;
			}
		}

		return $statuses;
	}

	private function buildRules(int $reportId): array
	{
		return $this->database->selectToArray('report-rule', ['line-id', 'text'], ['rid' => $reportId]);
	}

	private function idToCategory(int $category): string
	{
		return match ($category) {
			ReportEntity::CATEGORY_SPAM      => 'spam',
			ReportEntity::CATEGORY_ILLEGAL   => 'legal',
			ReportEntity::CATEGORY_VIOLATION => 'violation',
			default                          => 'other',
		};
	}
}
