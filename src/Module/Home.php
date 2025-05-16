<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Event\Event;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ZOT;

/**
 * Home module - Landing page of the current node
 */
class Home extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(User::getActorName());
		} elseif (ZOT::isRequest()) {
			$this->jsonExit(ZOT::getSiteInfo(), 'application/x-zot+json');
		}
	}

	protected function content(array $request = []): string
	{
		$basePath        = DI::appHelper()->getBasePath();
		$config          = DI::config();
		$eventDispatcher = DI::eventDispatcher();

		$eventDispatcher->dispatch(
			new Event(Event::HOME_INIT)
		);

		if (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserNickname())) {
			DI::baseUrl()->redirect('network');
		}

		if ($config->get('system', 'singleuser')) {
			DI::baseUrl()->redirect('/profile/' . $config->get('system', 'singleuser'));
		}

		$customHome    = '';
		$defaultHeader = ($config->get('config', 'sitename') ? DI::l10n()->t('Welcome to %s', $config->get('config', 'sitename')) : '');

		$homeFilePath = $basePath . '/home.html';
		$cssFilePath  = $basePath . '/home.css';

		if (file_exists($homeFilePath)) {
			$customHome = $homeFilePath;

			if (file_exists($cssFilePath)) {
				DI::page()->registerStylesheet('home.css', 'all');
			}
		}

		$login = Login::form(DI::args()->getQueryString(), Register::getPolicy() !== Register::CLOSED);

		$content = '';

		$content = $eventDispatcher->dispatch(
			new HtmlFilterEvent(HtmlFilterEvent::MOD_HOME_CONTENT, $content),
		)->getHtml();

		$tpl = Renderer::getMarkupTemplate('home.tpl');
		return Renderer::replaceMacros($tpl, [
			'$defaultheader' => $defaultHeader,
			'$customhome'    => $customHome,
			'$login'         => $login,
			'$content'       => $content,
		]);
	}
}
