<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Conversation\Factory\Channel as ChannelFactory;
use Friendica\Content\Conversation\Factory\Community as CommunityFactory;
use Friendica\Content\Conversation\Factory\Network as NetworkFactory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Protocol;
use Friendica\Core\Search;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Model\Circle;
use Friendica\Model\Contact;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Shows several timelines and other pages side by side as columns, similar to TweetDeck.
 *
 * Every column is the page itself embedded as an iframe with the "mode=column" template.
 * The list of columns is stored in the browser.
 */
class Deck extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var Page */
	protected $page;
	/** @var AppHelper */
	protected $appHelper;

	public function __construct(private readonly NetworkFactory $network, private readonly ChannelFactory $channel, private readonly UserDefinedChannel $userDefinedChannel, private readonly CommunityFactory $community, private readonly Database $database, private readonly IManagePersonalConfigValues $pConfig, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, Page $page, AppHelper $appHelper, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session   = $session;
		$this->page      = $page;
		$this->appHelper = $appHelper;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('deck');
		}

		if ($this->appHelper->getCurrentTheme() !== 'frio') {
			throw new NotImplementedException($this->t('This feature is only available with the frio theme.'));
		}

		Nav::setSelected('deck');

		$this->page->registerStylesheet(Theme::getPathForFile('css/deck.css'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/deck.js'));

		$timelines = $this->getTimelines($this->session->getLocalUserId());
		$pages     = [
			['path' => 'notifications/stream',   'title' => $this->t('Notifications')],
			['path' => 'notifications/system',   'title' => $this->t('System notifications')],
			['path' => 'notifications/personal', 'title' => $this->t('Personal notifications')],
			['path' => 'message',                'title' => $this->t('Messages')],
		];

		$config = [
			'timelines' => $timelines,
			'circles'   => $this->getCircles($this->session->getLocalUserId()),
			'groups'    => $this->getGroups($this->session->getLocalUserId()),
			'searches'  => $this->getSavedSearches($this->session->getLocalUserId()),
			'pages'     => $pages,
			'defaults'  => array_merge(array_slice(array_column($timelines, 'path'), 0, 1), ['notifications/stream']),
			'l10n'      => [
				'search'    => ['prompt' => $this->t('Search term'), 'title' => $this->t('Search')],
				'custom'    => ['prompt' => $this->t('Path of the page, e.g. network/circle/1'), 'title' => $this->t('Custom page')],
				'scrollTop' => $this->t('Scroll to top'),
				'reload'    => $this->t('Reload'),
				'moveLeft'  => $this->t('Move left'),
				'moveRight' => $this->t('Move right'),
				'remove'    => $this->t('Close column'),
				'change'    => $this->t('Change column type'),
			],
		];

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('deck.tpl'), [
			'$config'      => json_encode($config),
			'$add'         => $this->t('Add column'),
			'$new_post'    => $this->t('New post'),
			'$close'       => $this->t('Close'),
			'$compose_url' => 'compose?mode=column',
		]);
	}

	/**
	 * @return array[] List of ['path', 'title']
	 */
	private function getCircles(int $uid): array
	{
		return array_map(fn (array $circle): array => [
			'path'  => 'network/circle/' . $circle['id'],
			'title' => $circle['name'],
		], Circle::getByUserId($uid));
	}

	/**
	 * Groups the user is following
	 *
	 * @return array[] List of ['path', 'title']
	 */
	private function getGroups(int $uid): array
	{
		$contacts = Contact::selectToArray(
			['id', 'name', 'nick'],
			['uid'     => $uid, 'self' => false, 'blocked' => false, 'archive' => false, 'deleted' => false, 'pending' => false,
				'network' => Protocol::FEDERATED, 'contact-type' => Contact::TYPE_COMMUNITY, 'rel' => [Contact::SHARING, Contact::FRIEND]],
			['order' => ['name']],
		);

		return array_map(fn (array $contact): array => [
			'path'  => 'contact/' . $contact['id'] . '/conversations',
			'title' => $contact['name'] ?: $contact['nick'],
		], $contacts);
	}

	/**
	 * @return array[] List of ['path', 'title']
	 */
	private function getSavedSearches(int $uid): array
	{
		return array_map(fn (array $search): array => [
			'path'  => Search::getSearchPath($search['term']),
			'title' => $search['term'],
		], $this->database->selectToArray('search', ['term'], ['uid' => $uid], ['order' => ['term']]));
	}

	/**
	 * Timelines that are shown in the top menu or in the channels widget, as shown in the display settings
	 *
	 * @return array[] List of ['path', 'title', 'description']
	 */
	private function getTimelines(int $uid): array
	{
		$all = [];
		foreach ($this->network->getTimelines('network') as $timeline) {
			$all[] = $timeline;
		}

		$networkCodes = array_map(fn ($timeline) => $timeline->code, $all);

		foreach ([$this->channel->getTimelines($uid), $this->userDefinedChannel->selectByUid($uid), $this->community->getTimelines(true)] as $timelines) {
			foreach ($timelines as $timeline) {
				$all[] = $timeline;
			}
		}

		$menu   = $this->pConfig->get($uid, 'system', 'network_timelines', []) ?: $networkCodes;
		$widget = $this->pConfig->get($uid, 'system', 'enabled_timelines', []) ?: array_map(fn ($timeline) => $timeline->code, $all);

		$result = [];
		foreach ($all as $timeline) {
			if (in_array($timeline->code, $menu) || in_array($timeline->code, $widget)) {
				$result[] = [
					'path'        => $timeline->path ?? 'network?' . http_build_query(['channel' => $timeline->code]),
					'title'       => $timeline->label,
					'description' => $timeline->description,
				];
			}
		}

		return $result;
	}
}
