<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Overview of all circles of the user
 */
class Circles extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
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

		Nav::setSelected('network');
		$this->page['aside'] .= $this->sidebarMenu->getHTML($uid, Widget\SidebarMenu::CIRCLES);

		$circles = Model\Circle::getByUserId($uid);
		usort($circles, fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

		$items = [[
			'name' => $this->t('Contacts not in any circle'),
			'href' => 'circle/none',
		]];

		foreach ($circles as $circle) {
			$items[] = [
				'name'    => $circle['name'],
				'href'    => 'network/circle/' . $circle['id'],
				'actions' => [[
					'href'  => 'circle/' . $circle['id'],
					'label' => $this->t('Edit circle'),
					'icon'  => 'ri-pencil-line',
				]],
			];
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('overview_list.tpl'), [
			'$title' => $this->t('Circles'),
			'$intro' => '',
			'$links' => [],
			'$add'   => [
				'action'      => 'circle/new',
				'method'      => 'post',
				'hidden'      => [['name' => 'form_security_token', 'value' => self::getFormSecurityToken('circle_edit')]],
				'name'        => 'circle_name',
				'placeholder' => $this->t('Circle Name: '),
				'submit'      => $this->t('Create a new circle'),
			],
			'$filter' => $this->t('Filter circles'),
			'$items'  => $items,
			'$empty'  => '',
		]);
	}
}
