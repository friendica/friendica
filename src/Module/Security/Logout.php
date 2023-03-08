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

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Model\User\Cookie;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Logout module
 */
class Logout extends BaseModule
{
	/** @var ICanCache */
	protected $cache;
	/** @var Cookie */
	protected $cookie;
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, ICanCache $cache, Cookie $cookie, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->cache   = $cache;
		$this->cookie  = $cookie;
		$this->session = $session;
	}


	/**
	 * Process logout requests
	 */
	protected function rawContent(array $request = [])
	{
		$visitor_home = null;
		if ($this->session->getRemoteUserId()) {
			$visitor_home = Profile::getMyURL();
			$this->cache->delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");

		// If this is a trusted browser, redirect to the 2fa signout page
		if ($this->cookie->get('2fa_cookie_hash')) {
			$this->baseUrl->redirect('2fa/signout');
		}

		$this->cookie->clear();
		$this->session->clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			DI::sysmsg()->addInfo($this->t('Logged out.'));
			$this->baseUrl->redirect();
		}
	}
}
