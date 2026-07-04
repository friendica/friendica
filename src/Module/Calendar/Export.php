<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Calendar;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Event;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Controller to export a calendar from a given user
 */
class Export extends BaseModule
{
	public const EXPORT_ICAL = 'ical';
	public const EXPORT_CSV  = 'csv';

	public const DEFAULT_EXPORT = self::EXPORT_ICAL;

	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var AppHelper */
	protected $appHelper;

	public function __construct(
		AppHelper $appHelper,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		IHandleUserSessions $session,
		SystemMessages $sysMessages,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->sysMessages = $sysMessages;
		$this->appHelper   = $appHelper;
	}

	protected function rawContent(array $request = [])
	{
		$nickname = $this->parameters['nickname'] ?? null;
		if (!$nickname) {
			throw new HTTPException\BadRequestException();
		}

		$owner = Profile::load($this->appHelper, $nickname, false);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if (!$this->session->isAuthenticated() && $owner['hidewall']) {
			$this->baseUrl->redirect('profile/' . $nickname . '/restricted');
		}

		if (!$this->session->isAuthenticated() && !Feature::isEnabled($owner['uid'], Feature::PUBLIC_CALENDAR)) {
			$this->sysMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect('profile/' . $nickname);
		}

		$ownerUid = $owner['uid'];
		$format   = $this->parameters['format'] ?: static::DEFAULT_EXPORT;

		// Get the export data by uid
		$evexport = Event::exportListByUserId($ownerUid, $format);

		if (!$evexport["success"]) {
			if ($evexport["content"]) {
				$this->sysMessages->addNotice($this->t('This calendar format is not supported'));
			} else {
				$this->sysMessages->addNotice($this->t('No exportable data found'));
			}

			// If it is the own calendar return to the events page
			// otherwise to the profile calendar page
			if ($this->session->getLocalUserId() === $ownerUid) {
				$returnPath = 'calendar';
			} else {
				$returnPath = 'calendar/show/' . $this->parameters['nickname'];
			}

			$this->baseUrl->redirect($returnPath);
		}

		// If nothing went wrong we can echo the export content
		$this->response->setHeader(sprintf(
			'Content-Disposition: attachment; filename="%s-%s.%s"',
			$this->t('calendar'),
			$this->parameters['nickname'],
			$evexport["extension"],
		));

		switch ($format) {
			case static::EXPORT_ICAL:
				$this->response->setType(Response::TYPE_BLANK, 'text/ics');
				break;
			case static::EXPORT_CSV:
				$this->response->setType(Response::TYPE_BLANK, 'text/csv');
				break;
		}

		$this->response->addContent($evexport['content']);
	}
}
