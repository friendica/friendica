<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class CircleExport extends BaseModule
{
	public function __construct(
		private readonly AppHelper $appHelper,
		private readonly IHandleUserSessions $session,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$nickname = $this->parameters['nickname'] ?? null;
		$circleId = (int) ($this->parameters['circle_id'] ?? 0);
		if (empty($nickname) || ($circleId <= 0)) {
			throw new HTTPException\BadRequestException();
		}

		$owner = \Friendica\Model\Profile::load($this->appHelper, $nickname, false);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('Profile not found.'));
		}

		if (!$this->session->isAuthenticated() && !empty($owner['hidewall'])) {
			$this->baseUrl->redirect('profile/' . $nickname . '/restricted');
		}

		$circle = DBA::selectFirst('group', ['id', 'uid', 'name'], ['id' => $circleId, 'uid' => $owner['uid'], 'deleted' => false, 'public' => true]);
		if (!DBA::isResult($circle)) {
			throw new HTTPException\NotFoundException($this->t('Public circle not found.'));
		}

		$members = DBA::selectToArray('circle-member-view', ['contact-addr', 'contact-link'], ['circle-id' => $circleId, 'uid' => $owner['uid']], ['order' => ['contact-name']]);

		$stream = fopen('php://temp', 'r+');
		// @see https://github.com/mastodon/mastodon/blob/main/app/models/form/import.rb
		fputcsv($stream, ['List name', 'Account address']);
		foreach ($members as $member) {
			$accountAddress = trim((string) ($member['contact-addr'] ?: $member['contact-link']));
			$accountAddress = ltrim($accountAddress, '@');

			fputcsv($stream, [
				$circle['name'],
				$accountAddress,
			]);
		}

		rewind($stream);
		$content = stream_get_contents($stream) ?: '';
		fclose($stream);

		$filename = preg_replace('/[^a-z0-9_-]+/i', '-', (string) $circle['name']);
		$filename = trim((string) $filename, '-');
		if ($filename === '') {
			$filename = 'circle-' . $circleId;
		}

		$this->response->setHeader(sprintf('Content-Disposition: attachment; filename="lists-%s-%s.csv"', $nickname, $filename));
		$this->response->setType(Response::TYPE_BLANK, 'text/csv');
		$this->response->addContent($content);
	}
}
