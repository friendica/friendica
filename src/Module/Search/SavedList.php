<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Search;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Post\Repository\SavedSearch;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Overview of the saved searches of the user. Terms are added and removed via {@see Saved}.
 */
class SavedList extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly SavedSearch $savedSearch,
		private readonly Widget\SidebarMenu $sidebarMenu,
		private Page $page,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters,
		EventDispatcherInterface $eventDispatcher,
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
	}

	protected function content(array $request = []): string
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		Nav::setSelected('search');
		$this->page['aside'] .= $this->sidebarMenu->getHTML($uid, Widget\SidebarMenu::SEARCHES);
		if (Feature::isEnabled($uid, Feature::TRENDING_TAGS)) {
			$this->page['aside'] .= Widget\TrendingTags::getHTML();
		}

		$returnUrl = bin2hex('search/saved');

		$items = [];
		foreach ($this->savedSearch->selectTermsByUid($uid) as $term) {
			$items[] = [
				'name'    => $term,
				'href'    => Search::getSearchPath($term),
				'actions' => [[
					'href'    => 'search/saved/remove?term=' . urlencode($term) . '&return_url=' . $returnUrl,
					'label'   => $this->t('Remove term'),
					'icon'    => 'ri-delete-bin-line',
					'confirm' => true,
				]],
			];
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('overview_list.tpl'), [
			'$title' => $this->t('Tags'),
			'$intro' => '',
			'$links' => [],
			'$add'   => [
				'action'      => 'search/saved/add',
				'method'      => 'get',
				'hidden'      => [['name' => 'return_url', 'value' => $returnUrl]],
				'name'        => 'term',
				'placeholder' => $this->t('Search term'),
				'submit'      => $this->t('Save search'),
			],
			'$filter' => $this->t('Filter searches'),
			'$items'  => $items,
			'$empty'  => $this->t('You have no saved searches.'),
		]);
	}
}
