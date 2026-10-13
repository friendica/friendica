<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Overview of the channels of the user
 */
class Channels extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		Nav::setSelected('network');
		DI::page()['aside'] .= Widget\SidebarMenu::getHTML($uid, Widget\SidebarMenu::CHANNELS);

		$items = [];
		foreach (Widget::getChannelList($uid) as $channel) {
			// The timelines of the network module are not available in the channel module
			if (DI::NetworkFactory()->isTimeline($channel['ref'])) {
				continue;
			}

			$items[] = [
				'name'        => $channel['name'],
				'description' => $channel['description'],
				'href'        => 'channel/' . rawurlencode((string) $channel['ref']),
			];
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('overview_list.tpl'), [
			'$title' => $this->t('Channels'),
			'$intro' => '',
			'$links' => [
				['href' => 'settings/channels', 'label' => $this->t('Edit own channels')],
				['href' => 'settings/display', 'label' => $this->t('Select and order channels')],
			],
			'$add'    => null,
			'$filter' => $this->t('Filter channels'),
			'$items'  => $items,
			'$empty'  => $this->t('There are no channels.'),
		]);
	}
}
