<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/reports/
 */
class Reports extends BaseApi
{
	public function __construct(private readonly \Friendica\Moderation\Repository\Report $reportRepo, private readonly \Friendica\Moderation\Factory\Report $reportFactory, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	public function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);

		$request = $this->getRequest([
			'account_id' => '',      // ID of the account to report
			'status_ids' => [],      // Array of Statuses to attach to the report, for context
			'comment'    => '',      // Reason for the report (default max 1000 characters)
			'category'   => 'other', // Specify if the report is due to spam, violation of enumerated instance rules, or some other reason.
			'rule_ids'   => [],      // For violation category reports, specify the ID of the exact rules broken.
			'forward'    => false,   // If the account is remote, should the report be forwarded to the remote admin?
		], $request);

		$contact = Contact::getById($request['account_id'], ['id', 'gsid']);
		if (empty($contact)) {
			throw new HTTPException\NotFoundException('Account ' . $request['account_id'] . ' not found');
		}

		$report = $this->reportFactory->createFromReportsRequest(
			System::getRules(),
			Contact::getPublicIdByUserId(self::getCurrentUserID()),
			$contact['id'],
			$contact['gsid'],
			$request['comment'],
			$request['category'],
			$request['forward'],
			$request['status_ids'],
			$request['rule_ids'],
			self::getCurrentUserID(),
		);

		$report = $this->reportRepo->save($report);

		if ($report->forward && $report->id) {
			Worker::add(Worker::PRIORITY_LOW, 'ForwardReport', (int) $report->id);
		}

		$this->jsonExit([]);
	}
}
