<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Shows a filtered list of the posts of a profile (media, original posts, reshares, quotes, comments, activities)
 */
class Posts extends BaseProfile
{
	public function __construct(private Page $page, private readonly AppHelper $appHelper, private readonly IHandleUserSessions $session, private readonly IManageConfigValues $config, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		$profile = ProfileModel::load($this->appHelper, $this->parameters['nickname']);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\ForbiddenException($this->t('Access denied.'));
		}

		if (!empty($profile['hidewall']) && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $profile['nickname'] . '/restricted');
		}

		if (!$profile['net-publish']) {
			$this->page['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$cid = Contact::getPublicIdByUserId($profile['uid']);
		if (!$cid) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$uid      = $this->session->getLocalUserId();
		$filter   = $this->parameters['filter'];
		$is_owner = $uid == $profile['uid'];
		$raw      = isset($request['mode']) && ($request['mode'] == 'raw');

		$o = $raw ? '' : self::getTabsHTML($filter, $is_owner, $profile['nickname'], $profile['hide-friends']);

		return $o . match ($filter) {
			'activities' => Contact::getActivitiesFromId($cid, $uid, $request),
			'media'      => Contact::getPostsFromId($cid, $uid, true, $request),
			'original'   => Contact::getThreadsFromId($cid, $uid, 0, 0, $request, true),
			'quotes'     => Contact::getQuotesFromId($cid, $uid, $request),
			'reshares'   => Contact::getResharesFromId($cid, $uid, $request),
			default      => Contact::getPostsFromId($cid, $uid, false, $request),
		};
	}
}
