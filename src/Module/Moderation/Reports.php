<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Module\BaseModeration;
use Friendica\Module\Moderation\Utils\ReportUtil;
use Friendica\Module\Response;
use Friendica\Moderation\Repository\Report as ReportRepository;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Reports extends BaseModeration
{
	/** @var Database */
	private $database;
	/** @var ReportUtil */
	protected $reportUtil;
	/** @var ReportRepository */
	private $reportRepository;

	public function __construct(Database $database, Page $page, ReportUtil $reportUtil, ReportRepository $reportRepository, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database         = $database;
		$this->reportUtil       = $reportUtil;
		$this->reportRepository = $reportRepository;
	}

	protected function post(array $request = [])
	{
		// Handle bulk close reports
		if (isset($request['close_reports']) && isset($request['report_ids'])) {
			$reportIds = $request['report_ids'];
			if (is_string($reportIds)) {
				$reportIds = [$reportIds];
			}

			foreach ($reportIds as $reportId) {
				try {
					$this->reportRepository->setStatus((int) $reportId, \Friendica\Moderation\Entity\Report::STATUS_CLOSED);
				} catch (\Exception $e) {
					$this->logger->notice('Error closing report', ['report_id' => $reportId, 'exception' => $e]);
				}
			}
		}

		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$total = $this->database->count('report', ['status' => \Friendica\Moderation\Entity\Report::STATUS_OPEN]);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 10);

		$query = $this->database->p(
			"SELECT
	`report`.`id`, `report`.`cid`, `report`.`comment`, `report`.`forward`, `report`.`created`, `report`.`reporter-id`,
	`report`.`category-id`,
	(
		SELECT GROUP_CONCAT(`report-rule`.`text` ORDER BY `report-rule`.`line-id` SEPARATOR \"\n\")
		FROM `report-rule`
		WHERE `report-rule`.`rid` = `report`.`id`
		GROUP BY `report-rule`.`rid`
	) AS `rules`,
	`contact`.`micro`, `contact`.`name`, `contact`.`nick`, `contact`.`url`, `contact`.`addr`
FROM report
INNER JOIN `contact` ON `contact`.`id` = `report`.`cid`
WHERE `report`.`status` = ?
ORDER BY `report`.`created` DESC
LIMIT ?, ?",
			\Friendica\Moderation\Entity\Report::STATUS_OPEN,
			$pager->getStart(),
			$pager->getItemsPerPage(),
		);

		$reports = [];
		while ($report = $this->database->fetch($query)) {
			$report['posts']    = [];
			$report['created']  = DateTimeFormat::local($report['created'], DateTimeFormat::MYSQL);
			$report['category'] = $this->reportUtil->getReportCategoryName($report['category-id']);

			$reports[$report['id']] = $report;
		}
		$this->database->close($query);

		$condition = ["SELECT `post-view`.`created`, `post-view`.`guid`, `post-view`.`plink`, `post-view`.`title`, `post-view`.`body`, `report-post`.`rid`
			FROM `report-post` INNER JOIN `post-view` ON `report-post`.`uri-id` = `post-view`.`uri-id`"];
		$condition = DBA::mergeConditions($condition, ['rid' => array_keys($reports)]);
		$posts     = $this->database->p(array_shift($condition), $condition);
		while ($post = $this->database->fetch($posts)) {
			if (in_array($post['rid'], array_keys($reports))) {
				$post['created'] = DateTimeFormat::local($post['created'], DateTimeFormat::MYSQL);
				$post['body']    = BBCode::toPlaintext($post['body'] ?? '');

				$reports[$post['rid']]['posts'][] = $post;
			}
		}
		$this->database->close($posts);

		$t = Renderer::getMarkupTemplate('moderation/report/overview.tpl');
		return Renderer::replaceMacros($t, [
			// strings //
			'$title'       => $this->t('Moderation'),
			'$page'        => $this->t('List of reports'),
			'$description' => $this->t('This page display reports created by our or remote users.'),
			'$no_data'     => $this->t('No report exists at this node.'),

			'$h_reports'     => $this->t('Reports'),
			'$th_reports'    => [$this->t('Created'), $this->t('Photo'), $this->t('Name'), $this->t('Comment'), $this->t('Category')],
			'$select_all'    => $this->t('Select all'),
			'$close_reports' => $this->t('Close selected reports'),

			// values //
			'$reports'       => $reports,
			'$total_reports' => $this->tt('%s total report', '%s total reports', $total),
			'$paginate'      => $pager->renderFull($total),

			'$contacturl' => ['contact_url', $this->t('Profile URL'), '', $this->t('URL of the reported contact.')],
		]);
	}
}
