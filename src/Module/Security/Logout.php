<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\System;
use Friendica\Model\Profile;
use Friendica\Model\User\Cookie;
use Friendica\Module\Response;
use Friendica\Security\TwoFactor;
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
	/** @var IHandleSessions */
	protected $session;
	/** @var TwoFactor\Repository\TrustedBrowser */
	protected $trustedBrowserRepo;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, TwoFactor\Repository\TrustedBrowser $trustedBrowserRepo, ICanCache $cache, Cookie $cookie, IHandleSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->cache              = $cache;
		$this->cookie             = $cookie;
		$this->session            = $session;
		$this->trustedBrowserRepo = $trustedBrowserRepo;
	}


	/**
	 * Process logout requests
	 */
	protected function rawContent(array $request = [])
	{
		$visitor_home = null;
		if (remote_user()) {
			$visitor_home = Profile::getMyURL();
			$this->cache->delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");

		// Remove this trusted browser as it won't be able to be used ever again after the cookie is cleared
		if ($this->cookie->get('trusted')) {
			$this->trustedBrowserRepo->removeForUser(local_user(), $this->cookie->get('trusted'));
		}

		$this->cookie->clear();
		$this->session->clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			info($this->t('Logged out.'));
			$this->baseUrl->redirect();
		}
	}
}
