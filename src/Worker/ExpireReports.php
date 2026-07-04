<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class ExpireReports
{
	public static function execute()
	{
		self::cleanupExpiredReports(DI::dba(), DI::config(), DI::logger());
	}

	public static function cleanupExpiredReports(Database $database, IManageConfigValues $config, LoggerInterface $logger): void
	{
		$enabled = (int) ($config->get('system', 'dbclean-expire-limit') ?? 0);
		if ($enabled <= 0) {
			return;
		}

		self::deleteExpiredReports($database, $logger, ReportEntity::STATUS_CLOSED, DateTimeFormat::utc('now - 90 days'), 'closed');
		self::deleteExpiredReports($database, $logger, ReportEntity::STATUS_OPEN, DateTimeFormat::utc('now - 365 days'), 'open');
	}

	private static function deleteExpiredReports(Database $database, LoggerInterface $logger, int $status, string $threshold, string $label): void
	{
		$condition = [
			"`status` = ? AND COALESCE(`edited`, `created`) < ?",
			$status,
			$threshold,
		];

		$reports = $database->select('report', ['id'], $condition);
		if (empty($reports)) {
			return;
		}

		$rows = 0;
		while ($row = $database->fetch($reports)) {
			$database->delete('report-rule', ['rid' => $row['id']]);
			$database->delete('report-post', ['rid' => $row['id']]);
			$database->delete('report', ['id' => $row['id']]);
			$rows++;
		}
		$database->close($reports);

		$logger->notice('Deleted expired reports', ['label' => $label, 'rows' => $rows]);
	}
}
