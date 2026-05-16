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
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Register;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Summary extends BaseModeration
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, Page $page, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$accounts = [
			[$this->t('Personal Page'), 0],
			[$this->t('Organisation Page'), 0],
			[$this->t('News Page'), 0],
			[$this->t('Community Group'), 0],
			[$this->t('Channel Relay'), 0],
		];

		$users = 0;

		$accountTypeCountStmt = $this->database->p('SELECT `account-type`, COUNT(`uid`) AS `count` FROM `user` WHERE `uid` != ? GROUP BY `account-type`', 0);
		while ($AccountTypeCount = $this->database->fetch($accountTypeCountStmt)) {
			$accounts[$AccountTypeCount['account-type']][1] = $AccountTypeCount['count'];
			$users += $AccountTypeCount['count'];
		}
		$this->database->close($accountTypeCountStmt);

		$this->logger->debug('accounts', ['accounts' => $accounts]);

		$pending = Register::getPendingCount();

		$t = Renderer::getMarkupTemplate('moderation/summary.tpl');
		return Renderer::replaceMacros($t, [
			'$title'               => $this->t('Moderation'),
			'$page'                => $this->t('Summary'),
			'$users'               => [$this->t('Registered users'), $users],
			'$accounts'            => $accounts,
			'$pending'             => [$this->t('Pending registrations'), $pending],
			'$warningtext'         => [],
			'$account_type_header' => $this->t('Registered accounts by type'),
		]);
	}
}
