<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Contact\LocalRelationship\Repository\LocalRelationship;
use Friendica\Content\Conversation\StatusEditor;
use Friendica\Content\GroupManager;
use Friendica\Content\Nav;
use Friendica\Content\Widget\VCard;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact as ModelContact;
use Friendica\Module\Contact;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 *  Manages and show Contacts and their content
 */
class Conversations extends BaseModule
{
	public function __construct(L10n $l10n, private readonly LocalRelationship $localRelationship, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, private Page $page, private readonly StatusEditor $statusEditor, private readonly IHandleUserSessions $userSession, private readonly GroupManager $groupManager, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		// Backward compatibility: Ensure to use the public contact when the user contact is provided
		// Remove by version 2022.03
		$pcid = ModelContact::getPublicContactId(intval($this->parameters['id']), $this->userSession->getLocalUserId());
		if (!$pcid) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$contact = ModelContact::getAccountById($pcid);
		if (empty($contact)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		// Don't display contacts that are about to be deleted
		if ($contact['deleted'] || $contact['network'] == Protocol::PHANTOM) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$localRelationship = $this->localRelationship->getForUserContact($this->userSession->getLocalUserId(), $contact['id']);
		if ($localRelationship->rel === ModelContact::SELF) {
			$this->baseUrl->redirect('profile/' . $contact['nick']);
		}

		$raw = isset($request['mode']) && ($request['mode'] == 'raw');

		if (!$raw) {
			$this->statusEditor->registerAssets();

			$this->page['aside'] .= VCard::getHTML($contact, true);
		}

		Nav::setSelected('contacts');

		$output = '';

		if (!$contact['ap-posting-restricted'] && !$raw) {
			$ucid = 0;
			if ($contact['contact-type'] == ModelContact::TYPE_COMMUNITY) {
				$ucid = ModelContact::getUserContactId($contact['id'], $this->userSession->getLocalUserId());
			}

			if ($ucid) {
				$options = [
					'group_cid'            => $ucid,
					'contact_account_type' => $contact['contact-type'],
				];
			} else {
				$options = [
					'lockstate'            => ACL::getLockstateForUserId($this->userSession->getLocalUserId()) ? 'lock' : 'unlock',
					'acl'                  => ACL::getFullSelectorHTML($this->page, $this->userSession->getLocalUserId(), true, []),
					'bang'                 => '',
					'content'              => ($contact['contact-type'] == ModelContact::TYPE_COMMUNITY ? '!' : '@') . ($contact['addr'] ?: $contact['url']),
					'contact_account_type' => $contact['contact-type'],
				];
			}
			$output = $this->statusEditor->renderEditor($options);
		}

		Contact::setPageTitle($contact);
		if (!$raw) {
			$output .= Contact::getTabsHTML($contact, Contact::TAB_CONVERSATIONS);
		}
		$output .= ModelContact::getThreadsFromId($contact['id'], $this->userSession->getLocalUserId(), 0, 0, $request);

		// Group posts aren't set to seen in the timelines, so we do it here
		if ($contact['contact-type'] == ModelContact::TYPE_COMMUNITY) {
			$this->groupManager->markSeen($this->userSession->getLocalUserId(), $contact['id']);
		}

		return $output;
	}
}
