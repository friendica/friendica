<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\Content\Conversation;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\App\BaseURL;
use Friendica\App\Arguments;
use Friendica\App\Mode;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Database\DBA;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Profile page for managing personal personal notes.
 *
 * Provides the UI to create, view and paginate personal notes associated
 * with the currently authenticated local user.
 *
 * @package Friendica\Module\Profile
 */
class Notes extends BaseProfile
{
	protected AppHelper $appHelper;
	protected UserSession $userSession;
	protected Mode $mode;
	protected IManagePersonalConfigValues $pConfig;
	protected IManageConfigValues $config;
	protected Conversation $conversation;

	/**
	 * Notes constructor.
	 *
	 * @param AppHelper $appHelper
	 * @param UserSession $userSession
	 * @param L10n $l10n
	 * @param BaseURL $baseUrl
	 * @param Arguments $args
	 * @param LoggerInterface $logger
	 * @param Profiler $profiler
	 * @param \Friendica\Module\Response $response
	 * @param array $server
	 * @param array $parameters
	 * @param Mode $mode
	 * @param IManagePersonalConfigValues $pConfig
	 * @param IManageConfigValues $config
	 * @param Conversation $conversation
	 */
	public function __construct(AppHelper $appHelper, UserSession $userSession, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, \Friendica\Module\Response $response, array $server, array $parameters = [], Mode $mode, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, Conversation $conversation)
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->appHelper    = $appHelper;
		$this->userSession  = $userSession;
		$this->mode         = $mode;
		$this->pConfig      = $pConfig;
		$this->config       = $config;
		$this->conversation = $conversation;
	}

	/**
	 * Render the notes page content.
	 *
	 * @param array $request Optional request parameters
	 * @return string Rendered HTML for the notes page
	 * @throws ForbiddenException If no local user is logged in
	 */
	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			throw new ForbiddenException();
		}

		Nav::setSelected('home');

		$contactId = $this->appHelper->getContactId();

		$o = parent::getTabsHTML('notes', true, $this->userSession->getLocalUserNickname(), false);

		$o .= '<h3>' . $this->l10n->t('Personal notes') . '</h3>';

		$x = [
			'lockstate' => 'lock',
			'acl'       => ACL::getSelfOnlyHTML($this->userSession->getLocalUserId(), $this->l10n->t('Personal notes are visible only by yourself.')),
			'button'    => $this->l10n->t('Save'),
			'acl_data'  => '',
		];

		$o .= $this->conversation->statusEditor($x, $contactId);

		$condition = [
			'uid'        => $this->userSession->getLocalUserId(),
			'post-type'  => Item::PT_PERSONAL_NOTE,
			'gravity'    => Item::GRAVITY_PARENT,
			'contact-id' => $contactId,
		];

		if ($this->mode->isMobile()) {
			$itemsPerPage = $this->pConfig->get(
				$this->userSession->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				$this->config->get('system', 'itemspage_network_mobile'),
			);
		} else {
			$itemsPerPage = $this->pConfig->get(
				$this->userSession->getLocalUserId(),
				'system',
				'itemspage_network',
				$this->config->get('system', 'itemspage_network'),
			);
		}

		$pager = new Pager($this->l10n, $this->args->getQueryString(), $itemsPerPage);

		$params = [
			'order' => ['created' => true],
			'limit' => [$pager->getStart(), $pager->getItemsPerPage()],
		];
		$r = Post::selectThreadForUser($this->userSession->getLocalUserId(), ['uri-id'], $condition, $params);

		$count = 0;
		if (DBA::isResult($r)) {
			$notes = Post::toArray($r);
			$count = count($notes);
			$o .= $this->conversation->render($notes, Conversation::MODE_NOTES, false);
		}

		$o .= $pager->renderMinimal($count);
		return $o;
	}
}
