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
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Protocol\ActivityPub;

/**
 * Home module - Landing page of the current node
 */
class Home extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(User::getActorName());
		}
	}

	protected function content(array $request = []): string
	{
		$app = DI::app();
		$config = DI::config();

		// currently no returned data is used
		$ret = [];

		Hook::callAll('home_init', $ret);

		if (DI::userSession()->getLocalUserId() && ($app->getLoggedInUserNickname())) {
			DI::baseUrl()->redirect('network');
		}

		if ($config->get('system', 'singleuser')) {
			DI::baseUrl()->redirect('/profile/' . $config->get('system', 'singleuser'));
		}

		$customHome = '';
		$defaultHeader = ($config->get('config', 'sitename') ? DI::l10n()->t('Welcome to %s', $config->get('config', 'sitename')) : '');

		$homeFilePath = $app->getBasePath() . '/home.html';
		$cssFilePath = $app->getBasePath() . '/home.css';

		if (file_exists($homeFilePath)) {
			$customHome = $homeFilePath;

			if (file_exists($cssFilePath)) {
				DI::page()->registerStylesheet('home.css', 'all');
			}
		}

		$login = Login::form(DI::args()->getQueryString(), $config->get('config', 'register_policy') === Register::CLOSED ? 0 : 1);

		$content = '';
		Hook::callAll('home_content', $content);

		$tpl = Renderer::getMarkupTemplate('home.tpl');
		return Renderer::replaceMacros($tpl, [
			'$defaultheader' => $defaultHeader,
			'$customhome'    => $customHome,
			'$login'         => $login,
			'$content'       => $content,
		]);
	}
}
