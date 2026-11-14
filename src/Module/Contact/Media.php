<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Contact as ModelContact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Module\BaseProfile;
use Friendica\Module\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * GUI for media posts of a contact
 */
class Media extends BaseModule
{
	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, private readonly AppHelper $appHelper, LoggerInterface $logger, Profiler $profiler, Response $response, private readonly IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		$raw = isset($request['mode']) && ($request['mode'] == 'raw');

		$cid = $this->parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid]);
		if (empty($contact)) {
			throw new BadRequestException(DI::l10n()->t('Contact not found.'));
		}

		if (Model\Contact::isSelf($contact['id'], $this->userSession->getLocalUserId())) {
			$profile = ProfileModel::load($this->appHelper, $contact['nick']);
			$o       = $raw ? '' : BaseProfile::getTabsHTML('media', true, $profile['nickname'], $profile['hide-friends']);
		} else {
			DI::page()['aside'] = Widget\VCard::getHTML($contact);
			Contact::setPageTitle($contact);
			$o = $raw ? '' : Contact::getTabsHTML($contact, Contact::TAB_MEDIA);
		}

		$o .= ModelContact::getPostsFromUrl($contact['url'], $this->userSession->getLocalUserId(), true, $request);

		return $o;
	}
}
