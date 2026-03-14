<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Update;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact as ModelContact;
use Friendica\Model\Post;
use Friendica\Module\Contact as ContactModule;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous update module for the contact page
 *
 * @package Friendica\Module\Update
 */
final class Contact extends ContactModule
{
	private UserSession $userSession;

	/**
	 * Contact update module constructor.
	 *
	 * @param L10n $l10n
	 * @param BaseURL $baseUrl
	 * @param Arguments $args
	 * @param LoggerInterface $logger
	 * @param Profiler $profiler
	 * @param Response $response
	 * @param array $server
	 * @param array $parameters
	 * @param UserSession $userSession
	 */
	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [], UserSession $userSession)
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->userSession = $userSession;
	}

	/**
	 * Return the HTML update for a contact thread.
	 *
	 * @param array $request Request parameters; expects 'item' and optional 'last_received'
	 * @return void Sends HTML update and exits
	 * @throws ForbiddenException If no local user is logged in
	 * @throws NotFoundException If the contact or item cannot be found
	 */
	protected function rawContent(array $request = [])
	{
		if (!$this->userSession->getLocalUserId()) {
			throw new ForbiddenException();
		}

		$pcid = ModelContact::getPublicContactId(intval($this->parameters['id']), $this->userSession->getLocalUserId());
		if (!$pcid) {
			throw new NotFoundException();
		}

		$item = Post::selectFirst(['parent'], ['id' => $request['item']]);
		if (!DBA::isResult($item)) {
			throw new NotFoundException();
		}

		System::htmlUpdateExit(ModelContact::getThreadsFromId($pcid, $this->userSession->getLocalUserId(), true, $item['parent'] ?? 0, $request));
	}
}
