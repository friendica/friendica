<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPException;

/**
 * Loads a profile for the hCard page
 * @see http://microformats.org/wiki/hcard
 */
class HCard extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (DI::userSession()->getLocalUserId() && ($this->parameters['action'] ?? '') === 'view') {
			// A logged in user views a profile of a user
			$nickname = DI::userSession()->getLocalUserNickname();
		} elseif (empty($this->parameters['action'])) {
			// Show the profile hCard
			$nickname = $this->parameters['profile'];
		} else {
			throw new HTTPException\NotFoundException(DI::l10n()->t('No profile'));
		}

		$profile = User::getOwnerDataByNick($nickname);

		if (empty($profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$page = DI::page();

		if (!empty($profile['page-flags']) && in_array($profile['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_COMM_MAN])) {
			$page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
		}
		if (!empty($profile['openidserver'])) {
			$page['htmlhead'] .= '<link rel="openid.server" href="' . $profile['openidserver'] . '" />' . "\r\n";
		}
		if (!empty($profile['openid'])) {
			$delegate = ((strstr((string) $profile['openid'], '://')) ? $profile['openid'] : 'http://' . $profile['openid']);
			$page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
		}

		$baseUrl = (string) DI::baseUrl();

		$uri = urlencode('acct:' . $profile['nickname'] . '@' . DI::baseUrl()->getHost() . (DI::baseUrl()->getPath() ? '/' . DI::baseUrl()->getPath() : ''));

		$page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . ($profile['net-publish'] ? 'true' : 'false') . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $profile['poll'] . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . $baseUrl . '/xrd/?uri=' . $uri . '" />' . "\r\n";
		header('Link: <' . $baseUrl . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		foreach (['notify', 'poll'] as $dfrn) {
			$page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"" . $baseUrl . "/dfrn_{$dfrn}/{$nickname}\" />\r\n";
		}

		$block = (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated());

		// check if blocked
		if ($block) {
			$keywords = $profile['pub_keywords'] ?? '';
			$keywords = str_replace([',', ' ', ',,'], [' ', ',', ','], $keywords);
			if (strlen($keywords)) {
				$page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n";
			}
		}

		$page['aside'] = Profile::getVCardHtml($profile, $block, false);

		return '';
	}
}
