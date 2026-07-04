<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseModeration;
use Friendica\Module\Moderation\Utils\ReportUtil;
use Friendica\Module\Response;
use Friendica\Moderation\Entity\Report\Post as ReportPostEntity;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Moderation\Repository\Report as ReportRepository;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Reports extends BaseModeration
{
	/** @var ReportUtil */
	protected $reportUtil;

	public function __construct(private readonly Database $database, Page $page, ReportUtil $reportUtil, private readonly ReportRepository $reportRepository, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->reportUtil = $reportUtil;
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

		if (!empty($request['report_action']) && !empty($request['report_id'])) {
			$reportId = (int) $request['report_id'];
			$uid      = $this->session->getLocalUserId();
			if ($this->isFinalizedReport($reportId)) {
				$this->logger->notice('Skipping mutation on finalized report', ['report_id' => $reportId, 'action' => $request['report_action']]);
				$this->content($request);
				return;
			}

			try {
				switch ($request['report_action']) {
					case 'assign_self':
						$this->reportRepository->setAssignment($reportId, $uid, $uid);
						break;
					case 'unassign':
						$this->reportRepository->setAssignment($reportId, null, $uid);
						break;
					case 'resolve':
						$this->reportRepository->updateModerationState($reportId, [
							'resolution'      => ReportEntity::RESOLUTION_ACCEPTED,
							'status'          => ReportEntity::STATUS_CLOSED,
							'last-editor-uid' => $uid,
						]);
						break;
					case 'save_remarks':
						$this->reportRepository->setRemarks(
							$reportId,
							(string) ($request['public_remarks'] ?? ''),
							(string) ($request['private_remarks'] ?? ''),
							$uid,
						);
						break;
					case 'save_metadata':
						$this->saveMetadata($reportId, $request, $uid);
						break;
					case 'delete_reported_posts':
						$this->deleteReportedPosts($reportId, $uid);
						break;
					case 'delete_reported_post':
						$this->deleteReportedPost($reportId, (int) ($request['uri_id'] ?? 0), $uid);
						break;
					case 'block_target':
						$this->blockReportTarget($reportId, false, $uid);
						break;
					case 'block_target_purge':
						$this->blockReportTarget($reportId, true, $uid);
						break;
					case 'warn_target':
						$this->warnReportTarget($reportId, trim((string) ($request['warning_message'] ?? '')), $uid);
						break;
					case 'silence_local_target':
						$this->silenceLocalTarget($reportId, $uid);
						break;
				}
			} catch (\Throwable $e) {
				$this->logger->notice('Error handling report action', ['report_id' => $reportId, 'action' => $request['report_action'], 'exception' => $e]);
			}
		}

		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		if (!empty($request['id'])) {
			return $this->detail((int) $request['id'], $request);
		}

		return $this->overview($request);
	}

	private function overview(array $request = []): string
	{
		$statusFilter   = $request['status']   ?? 'open';
		$categoryFilter = $request['category'] ?? 'all';
		$assignedFilter = $request['assigned'] ?? 'all';
		$currentUserId  = $this->session->getLocalUserId();
		$condition      = [];

		if ($statusFilter !== 'all') {
			$condition['status'] = $statusFilter === 'closed' ? ReportEntity::STATUS_CLOSED : ReportEntity::STATUS_OPEN;
		}

		if ($categoryFilter !== 'all') {
			$categoryId = $this->categoryNameToId((string) $categoryFilter);
			if ($categoryId !== null) {
				$condition['category-id'] = $categoryId;
			}
		}

		if ($assignedFilter === 'mine') {
			$condition['assigned-uid'] = $currentUserId;
		} elseif ($assignedFilter === 'unassigned') {
			$condition['assigned-uid'] = null;
		}

		$total = $this->database->count('report', $condition);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 10);

		$reportSql = "SELECT
	`report`.`id`, `report`.`cid`, `report`.`comment`, `report`.`forward`, `report`.`created`, `report`.`reporter-id`,
	`report`.`category-id`, `report`.`status`, `report`.`resolution`, `report`.`assigned-uid`, `report`.`last-editor-uid`,
	(
		SELECT GROUP_CONCAT(`report-rule`.`text` ORDER BY `report-rule`.`line-id` SEPARATOR \"\n\")
		FROM `report-rule`
		WHERE `report-rule`.`rid` = `report`.`id`
		GROUP BY `report-rule`.`rid`
	) AS `rules`,
	`contact`.`micro`, `contact`.`name`, `contact`.`nick`, `contact`.`url`, `contact`.`addr`
FROM report
INNER JOIN `contact` ON `contact`.`id` = `report`.`cid`
		";
		$reportParams = [];
		$whereParts   = [];
		if (isset($condition['status'])) {
			$whereParts[]   = '`report`.`status` = ?';
			$reportParams[] = $condition['status'];
		}
		if (isset($condition['category-id'])) {
			$whereParts[]   = '`report`.`category-id` = ?';
			$reportParams[] = $condition['category-id'];
		}
		if ($assignedFilter === 'mine') {
			$whereParts[]   = '`report`.`assigned-uid` = ?';
			$reportParams[] = $currentUserId;
		} elseif ($assignedFilter === 'unassigned') {
			$whereParts[] = '`report`.`assigned-uid` IS NULL';
		}

		$reportSql .= ' WHERE ' . (empty($whereParts) ? '1=1' : implode(' AND ', $whereParts));
		$reportSql .= ' ORDER BY `report`.`created` DESC LIMIT ?, ?';
		$reportParams[] = $pager->getStart();
		$reportParams[] = $pager->getItemsPerPage();

		$query = $this->database->p($reportSql, ...$reportParams);

		$reports = [];
		while ($report = $this->database->fetch($query)) {
			$report['posts']        = [];
			$report['created']      = DateTimeFormat::local($report['created'], DateTimeFormat::MYSQL);
			$report['category']     = $this->reportUtil->getReportCategoryName($report['category-id']);
			$report['status_label'] = $report['status'] == ReportEntity::STATUS_CLOSED ? $this->t('Closed') : $this->t('Open');
			$report['detail_url']   = 'moderation/reports?id=' . (int) $report['id'] . '&status=' . rawurlencode($statusFilter) . '&category=' . rawurlencode($categoryFilter) . '&assigned=' . rawurlencode($assignedFilter);

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

			'$h_reports'             => $this->t('Reports'),
			'$th_reports'            => [$this->t('Created'), $this->t('Photo'), $this->t('Name'), $this->t('Comment'), $this->t('Category'), $this->t('Status')],
			'$select_all'            => $this->t('Select all'),
			'$close_reports'         => $this->t('Close selected reports'),
			'$open_reports'          => $this->t('Open reports'),
			'$closed_reports'        => $this->t('Closed reports'),
			'$all_reports'           => $this->t('All reports'),
			'$filter_status'         => $statusFilter,
			'$filter_assigned'       => $assignedFilter,
			'$category_filter_value' => $categoryFilter,
			'$category_filters'      => $this->buildCategoryFilterOptions($categoryFilter),
			'$assigned_filters'      => $this->buildAssignmentFilterOptions($assignedFilter),
			'$all_categories'        => $this->t('All categories'),

			// values //
			'$reports'       => $reports,
			'$total_reports' => $this->tt('%s total report', '%s total reports', $total),
			'$paginate'      => $pager->renderFull($total),

			'$contacturl' => ['contact_url', $this->t('Profile URL'), '', $this->t('URL of the reported contact.')],
		]);
	}

	private function detail(int $reportId, array $request = []): string
	{
		$report          = $this->reportRepository->selectOneById($reportId);
		$target          = Contact::getById($report->cid, ['id', 'micro', 'name', 'nick', 'url', 'addr', 'nurl']);
		$targetUserId    = !empty($target['url']) ? User::getIdForURL($target['url']) : 0;
		$selectedRuleIds = [];
		foreach ($report->rules as $rule) {
			$selectedRuleIds[] = $rule->lineId;
		}

		$reportArray = [
			'id'               => $report->id,
			'created'          => DateTimeFormat::local($report->created->format(DateTimeFormat::MYSQL), DateTimeFormat::MYSQL),
			'edited'           => $report->edited ? DateTimeFormat::local($report->edited->format(DateTimeFormat::MYSQL), DateTimeFormat::MYSQL) : null,
			'category'         => $this->reportUtil->getReportCategoryName($report->category),
			'category_id'      => $report->category,
			'comment'          => $report->comment,
			'public_remarks'   => $report->publicRemarks,
			'private_remarks'  => $report->privateRemarks,
			'forward'          => $report->forward,
			'status'           => $report->status,
			'status_label'     => $report->status === ReportEntity::STATUS_CLOSED ? $this->t('Closed') : $this->t('Open'),
			'resolution'       => $report->resolution,
			'assigned_uid'     => $report->assignedUid,
			'reporter'         => Contact::getById($report->reporterCid, ['id', 'micro', 'name', 'nick', 'url', 'addr']),
			'target'           => $target,
			'target_is_local'  => $targetUserId > 0,
			'target_user_id'   => $targetUserId,
			'posts'            => [],
			'rules'            => [],
			'rules_available'  => $this->buildRuleOptions($selectedRuleIds),
			'category_options' => $this->buildCategoryOptions($report->category),
			'is_final'         => $report->status === ReportEntity::STATUS_CLOSED,
		];

		foreach ($report->posts as $post) {
			$postRecord = Post::selectFirst(['uri-id', 'guid', 'plink', 'title', 'created'], ['uri-id' => $post->uriId, 'uid' => 0]);
			if (empty($postRecord)) {
				$postRecord = Post::selectFirst(['uri-id', 'guid', 'plink', 'title', 'created'], ['uri-id' => $post->uriId]);
			}

			$reportArray['posts'][] = [
				'uri_id'  => $post->uriId,
				'status'  => $post->status,
				'guid'    => $postRecord['guid']  ?? '',
				'plink'   => $postRecord['plink'] ?? '',
				'title'   => $postRecord['title'] ?? '',
				'created' => !empty($postRecord['created']) ? DateTimeFormat::local($postRecord['created'], DateTimeFormat::MYSQL) : '',
			];
		}

		foreach ($report->rules as $rule) {
			$reportArray['rules'][] = [
				'line_id' => $rule->lineId,
				'text'    => $rule->text,
			];
		}

		$t                = Renderer::getMarkupTemplate('moderation/report/show.tpl');
		$backToReportsUrl = 'moderation/reports?status=' . rawurlencode((string) ($request['status'] ?? 'open')) . '&category=' . rawurlencode((string) ($request['category'] ?? 'all')) . '&assigned=' . rawurlencode((string) ($request['assigned'] ?? 'all'));
		return Renderer::replaceMacros($t, [
			'$title'               => $this->t('Moderation'),
			'$page'                => $this->t('Report details'),
			'$back_to_reports'     => $this->t('Back to reports'),
			'$back_to_reports_url' => $backToReportsUrl,
			'$forwarded'           => $this->t('Forwarded'),
			'$not_forwarded'       => $this->t('Not forwarded'),
			'$assigned_user_id'    => $this->t('Assigned user id:'),
			'$unassigned'          => $this->t('Unassigned'),
			'$reporter_label'      => $this->t('Reporter:'),
			'$target_label'        => $this->t('Target:'),
			'$category_and_rules'  => $this->t('Category and rules'),
			'$category_label'      => $this->t('Category'),
			'$node_rules'          => $this->t('Node rules'),
			'$attached_posts'      => $this->t('Attached posts'),
			'$uri_id_label'        => $this->t('URI-ID'),
			'$status_label'        => $this->t('status'),
			'$guid_label'          => $this->t('GUID'),
			'$actions_heading'     => $this->t('Actions'),
			'$public_remarks'      => $this->t('Public remarks'),
			'$private_remarks'     => $this->t('Private remarks'),
			'$finalized_read_only' => $this->t('This report is finalized and read-only.'),
			'$save_remarks'        => $this->t('Save remarks'),
			'$assign_self'         => $this->t('Assign to me'),
			'$unassign'            => $this->t('Unassign'),
			'$resolve'             => $this->t('Mark resolved'),
			'$save_metadata'       => $this->t('Save category and rules'),
			'$delete_posts'        => $this->t('Delete all reported posts'),
			'$block_local'         => $this->t('Block local user'),
			'$block_remote'        => $this->t('Block remote contact'),
			'$block_remote_purge'  => $this->t('Block remote contact and purge content'),
			'$delete_post'         => $this->t('Delete this reported post'),
			'$warn_target'         => $this->t('Warn user'),
			'$warning_message'     => $this->t('Warning message'),
			'$silence_local'       => $this->t('Silence local user (future posts unlisted)'),
			'$report'              => $reportArray,
		]);
	}

	private function deleteReportedPosts(int $reportId, int $uid): void
	{
		$report = $this->reportRepository->selectOneById($reportId);

		$deletedCount = 0;
		foreach ($report->posts as $post) {
			Item::markForDeletion(['uri-id' => $post->uriId, 'deleted' => false]);
			$this->database->update('report-post', ['status' => ReportPostEntity::STATUS_DELETED], ['rid' => $reportId, 'uri-id' => $post->uriId]);
			$deletedCount++;
		}

		$this->reportRepository->updateModerationState($reportId, ['last-editor-uid' => $uid]);

		if ($deletedCount > 0) {
			$this->systemMessages->addInfo($this->tt('%s reported post marked for deletion', '%s reported posts marked for deletion', $deletedCount));
		} else {
			$this->systemMessages->addNotice($this->t('This report has no attached posts.'));
		}
	}

	private function deleteReportedPost(int $reportId, int $uriId, int $uid): void
	{
		if ($uriId <= 0) {
			$this->systemMessages->addNotice($this->t('Invalid reported post id.'));
			return;
		}

		$report = $this->reportRepository->selectOneById($reportId);
		$found  = false;
		foreach ($report->posts as $post) {
			if ($post->uriId === $uriId) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->systemMessages->addNotice($this->t('This post is not attached to the report.'));
			return;
		}

		Item::markForDeletion(['uri-id' => $uriId, 'deleted' => false]);
		$this->database->update('report-post', ['status' => ReportPostEntity::STATUS_DELETED], ['rid' => $reportId, 'uri-id' => $uriId]);
		$this->reportRepository->updateModerationState($reportId, ['last-editor-uid' => $uid]);
		$this->systemMessages->addInfo($this->t('Reported post marked for deletion.'));
	}

	private function blockReportTarget(int $reportId, bool $purge, int $uid): void
	{
		$report = $this->reportRepository->selectOneById($reportId);
		$target = Contact::getById($report->cid, ['id', 'name', 'url', 'nurl']);

		if (empty($target)) {
			$this->systemMessages->addNotice($this->t('Reported target contact not found.'));
			return;
		}

		$localUserId = !empty($target['url']) ? User::getIdForURL($target['url']) : 0;
		if ($localUserId > 0) {
			User::block($localUserId);
			$this->reportRepository->updateModerationState($reportId, ['last-editor-uid' => $uid]);
			$this->systemMessages->addInfo($this->t('The local user has been blocked.'));
			return;
		}

		if (Contact::isLocalById($report->cid)) {
			$this->systemMessages->addNotice($this->t('Could not map the reported local contact to a user account.'));
			return;
		}

		if (!Contact::block($report->cid, 'Blocked from moderation report #' . $reportId)) {
			$this->systemMessages->addNotice($this->t('Failed to block the remote contact.'));
			return;
		}

		if ($purge && !empty($target['nurl'])) {
			foreach (Contact::selectToArray(['id'], ['nurl' => $target['nurl']]) as $contact) {
				Worker::add(Worker::PRIORITY_LOW, 'Contact\\RemoveContent', $contact['id']);
			}
		}

		$this->reportRepository->updateModerationState($reportId, ['last-editor-uid' => $uid]);

		if ($purge) {
			$this->systemMessages->addInfo($this->t('The remote contact has been blocked and related content will be purged.'));
		} else {
			$this->systemMessages->addInfo($this->t('The remote contact has been blocked.'));
		}
	}

	private function warnReportTarget(int $reportId, string $warningMessage, int $uid): void
	{
		$this->logger->alert('Moderator warning issued', ['report_id' => $reportId, 'warning_message' => $warningMessage, 'uid' => $uid]);
		if ($warningMessage === '') {
			$this->systemMessages->addNotice($this->t('Please provide a warning message.'));
			return;
		}

		$report      = $this->reportRepository->selectOneById($reportId);
		$target      = Contact::getById($report->cid, ['id', 'url']);
		$localUserId = !empty($target['url']) ? User::getIdForURL($target['url']) : 0;

		$publicRemarks = trim($report->publicRemarks);
		if ($publicRemarks !== '') {
			$publicRemarks .= "\n\n";
		}
		$publicRemarks .= '[' . DateTimeFormat::utcNow() . '] ' . $this->t('Moderator warning:') . "\n" . $warningMessage;

		$this->reportRepository->setRemarks($reportId, $publicRemarks, $report->privateRemarks, $uid);

		if ($localUserId <= 0) {
			$this->systemMessages->addNotice($this->t('Warning text has been saved, but automatic delivery is only available for local users.'));
			return;
		}

		$user = User::getById($localUserId, ['uid', 'username', 'email', 'language']);
		if (empty($user['email'])) {
			$this->systemMessages->addNotice($this->t('Warning text has been saved, but the local user has no deliverable email address.'));
			return;
		}

		try {
			$email = DI::emailer()
				->newSystemMail()
				->withMessage(
					$this->t('Moderation warning from %s', $this->baseUrl->getHost()),
					$this->t('Your recent activity was reported to the moderation team. Please review this warning message.'),
					$warningMessage,
				)
				->forUser($user)
				->withRecipient($user['email'])
				->build();

			if (DI::emailer()->send($email)) {
				$this->logger->info('Moderator warning sent successfully', ['report_id' => $reportId, 'uid' => $uid, 'target_uid' => $localUserId]);
				$this->systemMessages->addInfo($this->t('Warning sent to the local user and stored in public remarks.'));
			} else {
				$this->logger->notice('Warning delivery failed', ['report_id' => $reportId, 'uid' => $uid, 'target_uid' => $localUserId]);
				$this->systemMessages->addNotice($this->t('Warning text was stored, but sending the email failed.'));
			}
		} catch (\Throwable $exception) {
			$this->logger->notice('Warning delivery failed', ['report_id' => $reportId, 'uid' => $localUserId, 'exception' => $exception]);
			$this->systemMessages->addNotice($this->t('Warning text was stored, but sending the email failed.'));
		}
	}

	private function silenceLocalTarget(int $reportId, int $uid): void
	{
		$report      = $this->reportRepository->selectOneById($reportId);
		$target      = Contact::getById($report->cid, ['id', 'url']);
		$localUserId = !empty($target['url']) ? User::getIdForURL($target['url']) : 0;

		if ($localUserId <= 0) {
			$this->systemMessages->addNotice($this->t('This action is only available for local users.'));
			return;
		}

		DI::pConfig()->set($localUserId, 'system', 'unlisted', true);

		$publicContactId = Contact::getPublicIdByUserId($localUserId);
		if (!empty($publicContactId)) {
			Contact::hide($publicContactId);
		}

		$this->reportRepository->updateModerationState($reportId, ['last-editor-uid' => $uid]);
		$this->systemMessages->addInfo($this->t('Local user silenced: future posts will be unlisted, and public community visibility is reduced.'));
	}

	private function isFinalizedReport(int $reportId): bool
	{
		try {
			return $this->reportRepository->selectOneById($reportId)->status === ReportEntity::STATUS_CLOSED;
		} catch (\Throwable) {
			return false;
		}
	}

	private function saveMetadata(int $reportId, array $request, int $uid): void
	{
		$category   = (string) ($request['category'] ?? '');
		$categoryId = $this->categoryNameToId($category);
		if ($categoryId === null) {
			return;
		}

		$ruleIds = $request['rule_ids'] ?? [];
		if (is_string($ruleIds)) {
			$ruleIds = [$ruleIds];
		}

		$this->database->update('report', [
			'category-id'     => $categoryId,
			'edited'          => DateTimeFormat::utcNow(),
			'last-editor-uid' => $uid,
		], ['id' => $reportId]);

		$this->database->delete('report-rule', ['rid' => $reportId]);
		if ($categoryId === ReportEntity::CATEGORY_VIOLATION) {
			$rules = System::getRules(true);
			foreach ($ruleIds as $lineId) {
				$lineId = (int) $lineId;
				$this->database->insert('report-rule', [
					'rid'     => $reportId,
					'line-id' => $lineId,
					'text'    => $rules[$lineId] ?? '',
				]);
			}
		}
	}

	private function buildCategoryOptions(int $selectedCategory): array
	{
		return [
			['value' => 'spam', 'label' => $this->t('Spam'), 'selected' => $selectedCategory === ReportEntity::CATEGORY_SPAM],
			['value' => 'legal', 'label' => $this->t('Illegal Content'), 'selected' => $selectedCategory === ReportEntity::CATEGORY_ILLEGAL],
			['value' => 'violation', 'label' => $this->t('Rules Violation'), 'selected' => $selectedCategory === ReportEntity::CATEGORY_VIOLATION],
			['value' => 'other', 'label' => $this->t('Other'), 'selected' => $selectedCategory === ReportEntity::CATEGORY_OTHER],
		];
	}

	private function buildCategoryFilterOptions(string $selectedCategory): array
	{
		$filters = [
			['value' => 'all', 'label' => $this->t('All categories'), 'selected' => $selectedCategory === 'all'],
			['value' => 'spam', 'label' => $this->t('Spam'), 'selected' => $selectedCategory === 'spam'],
			['value' => 'legal', 'label' => $this->t('Illegal Content'), 'selected' => $selectedCategory === 'legal'],
			['value' => 'violation', 'label' => $this->t('Rules Violation'), 'selected' => $selectedCategory === 'violation'],
			['value' => 'other', 'label' => $this->t('Other'), 'selected' => $selectedCategory === 'other'],
		];

		foreach (array_keys($filters) as $index) {
			$filters[$index]['last'] = $index === array_key_last($filters);
		}

		return $filters;
	}

	private function buildAssignmentFilterOptions(string $selectedAssigned): array
	{
		$filters = [
			['value' => 'all', 'label' => $this->t('All assignments'), 'selected' => $selectedAssigned === 'all'],
			['value' => 'mine', 'label' => $this->t('Assigned to me'), 'selected' => $selectedAssigned === 'mine'],
			['value' => 'unassigned', 'label' => $this->t('Unassigned'), 'selected' => $selectedAssigned === 'unassigned'],
		];

		foreach (array_keys($filters) as $index) {
			$filters[$index]['last'] = $index === array_key_last($filters);
		}

		return $filters;
	}

	private function buildRuleOptions(array $selectedRuleIds): array
	{
		$rules = [];
		foreach (System::getRules(true) as $lineId => $text) {
			$rules[] = [
				'line_id'  => (int) $lineId,
				'text'     => $text,
				'selected' => in_array((int) $lineId, $selectedRuleIds, true),
			];
		}

		return $rules;
	}

	private function categoryNameToId(string $category): ?int
	{
		return match ($category) {
			'spam'      => ReportEntity::CATEGORY_SPAM,
			'legal'     => ReportEntity::CATEGORY_ILLEGAL,
			'violation' => ReportEntity::CATEGORY_VIOLATION,
			'other'     => ReportEntity::CATEGORY_OTHER,
			default     => null,
		};
	}
}
