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

namespace Friendica\Core\Session\Type;

use Friendica\App;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Model\User\Cookie;
use SessionHandlerInterface;

/**
 * The native Session class which uses the PHP internal Session functions
 */
class Native extends AbstractSession implements IHandleSessions
{
	public function __construct(App\BaseURL $baseURL, SessionHandlerInterface $handler = null)
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', (int)Cookie::HTTPONLY);

		if ($baseURL->getScheme() === 'https') {
			ini_set('session.cookie_secure', 1);
		}

		if (isset($handler)) {
			session_set_save_handler($handler);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function start(): IHandleSessions
	{
		session_start();
		return $this;
	}
}
