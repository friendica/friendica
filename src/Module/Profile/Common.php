<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Common extends BaseProfile
{
	public function __construct(private readonly AppHelper $appHelper, private readonly IHandleUserSessions $userSession, private readonly IManageConfigValues $config, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->userSession->isAuthenticated()) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		Nav::setSelected('home');

		$nickname = $this->parameters['nickname'];

		$profile = Profile::load($this->appHelper, $nickname);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if (!empty($profile['hide-friends'])) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$displayCommonTab = $this->userSession->isAuthenticated() && $profile['uid'] != $this->userSession->getLocalUserId();

		if (!$displayCommonTab) {
			$this->baseUrl->redirect('profile/' . $nickname . '/contacts');
		};

		$o = self::getTabsHTML('contacts', false, $profile['nickname'], $profile['hide-friends']);

		$tabs = self::getContactFilterTabs('profile/' . $nickname, 'common', $displayCommonTab);

		$sourceId = Contact::getIdForURL($this->userSession->getMyUrl());
		$targetId = Contact::getPublicIdByUserId($profile['uid']);

		$condition = [
			'blocked' => false,
			'deleted' => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::FEED],
		];

		$total = Contact\Relation::countCommon($sourceId, $targetId, $condition);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$commonFollows = Contact\Relation::listCommon($sourceId, $targetId, $condition, $pager->getItemsPerPage(), $pager->getStart());

		// Contact list is obtained from the visited profile user, but the contact display is visitor dependent
		$contacts = array_map(
			function ($contact) {
				$contact = Contact::selectFirst(
					[],
					['uri-id' => $contact['uri-id'], 'uid' => [0, $this->userSession->getLocalUserId()]],
					['order'  => ['uid' => 'DESC']],
				);
				return Module\Contact::getContactTemplateVars($contact);
			},
			$commonFollows,
		);

		$title = $this->tt('Common contact (%s)', 'Common contacts (%s)', $total);
		$desc  = $this->t(
			'Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).',
			htmlentities((string) $profile['name'], ENT_COMPAT, 'UTF-8'),
		);

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title' => $title,
			'$desc'  => $desc,
			'$tabs'  => $tabs,

			'$noresult_label' => $this->t('No common contacts.'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
