<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\PageInfo;
use Friendica\DI;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Creates a bookmarklet
 * Shows either a editor browser or adds the given bookmarklet to the current user
 */
class Bookmarklet extends BaseModule
{
	protected function content(array $request = []): string
	{
		$_GET['mode'] = 'minimal';

		$config = DI::config();

		if (!DI::userSession()->getLocalUserId()) {
			$output = '<h2>' . DI::l10n()->t('Sign in') . '</h2>';
			$output .= Login::form(DI::args()->getQueryString(), Register::getPolicy() !== Register::CLOSED);
			return $output;
		}

		$referer = Strings::normaliseLink($_SERVER['HTTP_REFERER'] ?? '');
		$page    = Strings::normaliseLink(DI::baseUrl() . "/bookmarklet");

		if (!strstr($referer, $page)) {
			if (empty($_REQUEST["url"])) {
				throw new HTTPException\BadRequestException(DI::l10n()->t('This page is missing a url parameter.'));
			}

			$content = "\n" . PageInfo::getFooterFromUrl($_REQUEST['url']);

			$x = [
				'title'   => trim($_REQUEST['title'] ?? '', '*'),
				'content' => $content
			];
			$output = DI::conversation()->statusEditor($x, 0, false);
			$output .= "<script>window.resizeTo(800,550);</script>";
		} else {
			$output = '<h2>' . DI::l10n()->t('The post was created') . '</h2>';
			$output .= "<script>window.close()</script>";
		}

		return $output;
	}
}
