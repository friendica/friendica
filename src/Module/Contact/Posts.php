<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Contact\LocalRelationship\Repository\LocalRelationship;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 *  Show a contact posts and comments
 */
class Posts extends BaseModule
{
	/**
	 * @var LocalRelationship
	 */
	private $localRelationship;
	/**
	 * @var App\Page
	 */
	private $page;
	/**
	 * @var IHandleUserSessions
	 */
	private $userSession;

	public function __construct(L10n $l10n, LocalRelationship $localRelationship, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Page $page, IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->localRelationship = $localRelationship;
		$this->page              = $page;
		$this->userSession       = $userSession;
	}

	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		// Backward compatibility: Ensure to use the public contact when the user contact is provided
		// Remove by version 2022.03
		$pcid = Model\Contact::getPublicContactId(intval($this->parameters['id']), $this->userSession->getLocalUserId());
		if (!$pcid) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$contact = Model\Contact::getById($pcid);
		if (!DBA::isResult($contact)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		// Don't display contacts that are about to be deleted
		if ($contact['deleted'] || $contact['network'] == Protocol::PHANTOM) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$localRelationship = $this->localRelationship->getForUserContact($this->userSession->getLocalUserId(), $contact['id']);
		if ($localRelationship->rel === Model\Contact::SELF) {
			$this->baseUrl->redirect('profile/' . $contact['nick']);
		}

		$this->page['aside'] .= Widget\VCard::getHTML($contact);

		Nav::setSelected('contact');

		Contact::setPageTitle($contact);

		$o = Contact::getTabsHTML($contact, Contact::TAB_POSTS);

		$o .= Model\Contact::getPostsFromId($contact['id'], $this->userSession->getLocalUserId(), false, $request['last_created'] ?? '');

		return $o;
	}
}
