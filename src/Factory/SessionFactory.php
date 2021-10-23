<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Factory;

use Friendica\App;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Cache\Enum\Type;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating a valid Session for this run
 */
class SessionFactory
{
	/** @var string The plain, PHP internal session management */
	const HANDLER_NATIVE = 'native';
	/** @var string Using the database for session management */
	const HANDLER_DATABASE = 'database';
	/** @var string Using the cache for session management */
	const HANDLER_CACHE = 'cache';

	const HANDLER_DEFAULT = self::HANDLER_DATABASE;

	/**
	 * @param App\Mode        $mode
	 * @param App\BaseURL     $baseURL
	 * @param IConfig         $config
	 * @param Database        $dba
	 * @param ICache          $cache
	 * @param LoggerInterface $logger
	 * @param array           $server
	 *
	 * @return Session\ISession
	 */
	public function createSession(App\Mode $mode, App\BaseURL $baseURL, IConfig $config, Database $dba, ICache $cache, LoggerInterface $logger, Profiler $profiler, array $server = [])
	{
		$profiler->startRecording('session');
		$session = null;

		try {
			if ($mode->isInstall() || $mode->isBackend()) {
				$session = new Session\Memory();
			} else {
				$session_handler = $config->get('system', 'session_handler', self::HANDLER_DEFAULT);
				$handler = null;

				switch ($session_handler) {
					case self::HANDLER_DATABASE:
						$handler = new Session\Handler\Database($dba, $logger, $server);
						break;
					case self::HANDLER_CACHE:
						// In case we're using the db as cache driver, use the native db session, not the cache
						if ($config->get('system', 'cache_driver') === Type::DATABASE) {
							$handler = new Session\Handler\Database($dba, $logger, $server);
						} else {
							$handler = new Session\Handler\Cache($cache);
						}
						break;
				}

				$session = new Session\Native($baseURL, $handler);
			}
		} finally {
			$profiler->stopRecording();
			return $session;
		}
	}
}
