<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous HTML fragment provider for frio contact hovercards
 */
class Hovercard extends BaseModule
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IHandleUserSessions */
	private $userSession;

	public function __construct(IHandleUserSessions $userSession, IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config      = $config;
		$this->userSession = $userSession;
	}

	protected function rawContent(array $request = [])
	{
		$contact_url = $request['url'] ?? '';

		// Get out if the system doesn't have public access allowed
		if ($this->config->get('system', 'block_public') && !$this->userSession->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		/* Possible formats for relative URLs that need to be converted to the absolute contact URL:
		 * - contact/redir/123456
		 * - contact/123456/conversations
		 */
		if (str_starts_with($contact_url, 'contact/') && preg_match('/(\d+)/', $contact_url, $matches)) {
			$remote_contact = Contact::selectFirst(['nurl'], ['id' => $matches[1]]);
			$contact_url    = $remote_contact['nurl'] ?? '';
		}

		if (!$contact_url) {
			throw new HTTPException\BadRequestException();
		}

		// Search for contact data
		// Look if the local user has got the contact
		if ($this->userSession->isAuthenticated()) {
			$contact = Contact::getByURLForUser($contact_url, $this->userSession->getLocalUserId());
		} else {
			$contact = Contact::getByURL($contact_url, false);
		}

		if (!count($contact)) {
			throw new HTTPException\NotFoundException();
		}

		$this->httpExit(Widget\Hovercard::getHTML($contact, $this->userSession->getLocalUserId()));
	}
}
