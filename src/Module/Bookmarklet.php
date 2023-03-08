<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
			$output = '<h2>' . DI::l10n()->t('Login') . '</h2>';
			$output .= Login::form(DI::args()->getQueryString(), intval($config->get('config', 'register_policy')) === Register::CLOSED ? false : true);
			return $output;
		}

		$referer = Strings::normaliseLink($_SERVER['HTTP_REFERER'] ?? '');
		$page = Strings::normaliseLink(DI::baseUrl() . "/bookmarklet");

		if (!strstr($referer, $page)) {
			if (empty($_REQUEST["url"])) {
				throw new HTTPException\BadRequestException(DI::l10n()->t('This page is missing a url parameter.'));
			}

			$content = "\n" . PageInfo::getFooterFromUrl($_REQUEST['url']);

			$x = [
				'title'            => trim($_REQUEST['title'] ?? '', '*'),
				'content'          => $content
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
