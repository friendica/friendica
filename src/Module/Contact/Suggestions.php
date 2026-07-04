<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Model\Contact;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Network\HTTPException;

class Suggestions extends \Friendica\BaseModule
{
	public function __construct(private App\Page $page, private readonly IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$this->page['aside'] .= Widget::findPeople();
		$this->page['aside'] .= Widget::follow();

		$contacts = Contact\Relation::getCachedSuggestions($this->session->getLocalUserId());
		if (!$contacts) {
			return $this->t('No suggestions available. If this is a new site, please try again in 24 hours.');
		}

		$entries = [];
		foreach ($contacts as $contact) {
			$entries[] = ModuleContact::getContactTemplateVars($contact);
		}

		$tpl = Renderer::getMarkupTemplate('contact/list.tpl');

		return Renderer::replaceMacros($tpl, [
			'$title'    => $this->t('Friend Suggestions'),
			'$contacts' => $entries,
		]);
	}
}
