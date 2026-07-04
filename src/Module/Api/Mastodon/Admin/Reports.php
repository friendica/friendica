<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Admin;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Core\System;
use Friendica\Factory\Api\Mastodon\Report as MastodonReportFactory;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Moderation\Repository\Report as ReportRepository;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/admin/reports/
 */
class Reports extends BaseApi
{
	public function __construct(private readonly Database $database, private readonly ReportRepository $reportRepository, private readonly MastodonReportFactory $mstdnReportFactory, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function get(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$this->checkModeratorAccess();

		$request = $this->getRequest([
			'resolved'          => null,
			'account_id'        => '',
			'target_account_id' => '',
			'min_id'            => 0,
			'max_id'            => 0,
			'since_id'          => 0,
			'limit'             => 100,
		], $request);

		if (!empty($this->parameters['id'])) {
			$report = $this->mstdnReportFactory->createFromReportEntity($this->reportRepository->selectOneById((int) $this->parameters['id']));
			$this->jsonExit($report);
		}

		$condition = [];
		$params    = ['order' => ['id' => true], 'limit' => (int) $request['limit']];

		if ($request['resolved'] !== null && $request['resolved'] !== '') {
			$condition['status'] = filter_var($request['resolved'], FILTER_VALIDATE_BOOLEAN) ? ReportEntity::STATUS_CLOSED : ReportEntity::STATUS_OPEN;
		}

		$reporterId = $this->resolveAccountIdToContactId((string) $request['account_id']);
		if ($reporterId) {
			$condition['reporter-id'] = $reporterId;
		}

		$targetId = $this->resolveAccountIdToContactId((string) $request['target_account_id']);
		if ($targetId) {
			$condition['cid'] = $targetId;
		}

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ['`id` < ?', (int) $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ['`id` > ?', (int) $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ['`id` > ?', (int) $request['min_id']]);
		}

		$rows = $this->database->selectToArray('report', [], $condition, $params);

		$reports = [];
		foreach ($rows as $row) {
			self::setBoundaries((int) $row['id']);
			$reports[] = $this->mstdnReportFactory->createFromReportEntity($this->reportRepository->selectOneById((int) $row['id']));
		}

		self::setLinkHeader();
		$this->jsonExit($reports);
	}

	/**
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function put(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$this->checkModeratorAccess();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$request = $this->getRequest([
			'category' => '',
			'rule_ids' => [],
		], $request);

		$existing = $this->reportRepository->selectOneById((int) $this->parameters['id']);
		if ($existing->status === ReportEntity::STATUS_CLOSED) {
			$this->logAndJsonError(403, $this->errorFactory->Forbidden());
		}

		if ($request['category'] === '') {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$categoryId = $this->categoryToId($request['category']);
		if ($categoryId === null) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$this->database->update('report', [
			'category-id'     => $categoryId,
			'edited'          => DateTimeFormat::utcNow(),
			'last-editor-uid' => self::getCurrentUserID(),
		], ['id' => (int) $this->parameters['id']]);

		$this->database->delete('report-rule', ['rid' => (int) $this->parameters['id']]);
		if ($categoryId === ReportEntity::CATEGORY_VIOLATION && !empty($request['rule_ids'])) {
			$rules = System::getRules(true);
			foreach ((array) $request['rule_ids'] as $lineId) {
				$this->database->insert('report-rule', [
					'rid'     => (int) $this->parameters['id'],
					'line-id' => (int) $lineId,
					'text'    => $rules[(int) $lineId] ?? '',
				]);
			}
		}

		$this->jsonExit($this->mstdnReportFactory->createFromReportEntity($this->reportRepository->selectOneById((int) $this->parameters['id'])));
	}

	/**
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$this->checkModeratorAccess();

		if (empty($this->parameters['id']) || empty($this->parameters['action'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$reportId = (int) $this->parameters['id'];
		$uid      = self::getCurrentUserID();
		$existing = $this->reportRepository->selectOneById($reportId);

		switch ($this->parameters['action']) {
			case 'assign_to_self':
				if ($existing->status === ReportEntity::STATUS_CLOSED) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				$this->reportRepository->setAssignment($reportId, $uid, $uid);
				break;
			case 'unassign':
				if ($existing->status === ReportEntity::STATUS_CLOSED) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				$this->reportRepository->setAssignment($reportId, null, $uid);
				break;
			case 'resolve':
				if ($existing->status === ReportEntity::STATUS_CLOSED) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				$this->reportRepository->updateModerationState($reportId, [
					'resolution'      => ReportEntity::RESOLUTION_ACCEPTED,
					'status'          => ReportEntity::STATUS_CLOSED,
					'last-editor-uid' => $uid,
				]);
				break;
			case 'reopen':
				if ($existing->status === ReportEntity::STATUS_OPEN) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				$this->reportRepository->updateModerationState($reportId, [
					'resolution'      => null,
					'status'          => ReportEntity::STATUS_OPEN,
					'last-editor-uid' => $uid,
				]);
				break;
			default:
				$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$this->jsonExit($this->mstdnReportFactory->createFromReportEntity($this->reportRepository->selectOneById($reportId)));
	}

	private function categoryToId(string $category): ?int
	{
		return match ($category) {
			'spam'      => ReportEntity::CATEGORY_SPAM,
			'legal'     => ReportEntity::CATEGORY_ILLEGAL,
			'violation' => ReportEntity::CATEGORY_VIOLATION,
			'other'     => ReportEntity::CATEGORY_OTHER,
			default     => null,
		};
	}

	private function resolveAccountIdToContactId(string $accountId): ?int
	{
		if ($accountId === '' || !ctype_digit($accountId)) {
			return null;
		}

		$contact = DBA::selectFirst('account-user-view', ['id'], ['pid' => (int) $accountId], ['order' => ['uid' => true]]);
		return $contact['id'] ?? null;
	}
}
