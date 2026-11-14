<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;

/**
 * Renders a back button that is only visible in SPA mode when there is a history entry to go back to.
 *
 * For links in other templates use the attribute "data-spa-back" directly, see "bindBackButton" in spa-unpoly-nav.js.
 */
class SpaBackButton
{
	public function __construct(private readonly L10n $l10n, private readonly IManagePersonalConfigValues $pConfig, private readonly IHandleUserSessions $session) {}

	/**
	 * @return string The button markup, empty when the current user doesn't use the SPA mode
	 */
	public function render(): string
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid || !$this->pConfig->get($uid, 'system', 'enable_spa')) {
			return '';
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('spa_back.tpl'), ['$back' => $this->l10n->t('Back')]);
	}
}
