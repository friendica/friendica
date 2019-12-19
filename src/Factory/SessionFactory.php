<?php

namespace Friendica\Factory;

use Friendica\App;
use Friendica\Core\Cache\Cache;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\User\Cookie;
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
	 * @param IConfiguration   $config
	 * @param Cookie          $cookie
	 * @param Database        $dba
	 * @param ICache          $cache
	 * @param LoggerInterface $logger
	 * @param array           $server
	 *
	 * @return Session\ISession
	 */
	public function createSession(App\Mode $mode, App\BaseURL $baseURL, IConfiguration $config, Cookie $cookie, Database $dba, ICache $cache, LoggerInterface $logger, Profiler $profiler, array $server = [])
	{
		$stamp1  = microtime(true);
		$session = null;

		try {
			if ($mode->isInstall() || $mode->isBackend()) {
				$session = new Session\Memory($cookie);
			} else {
				$session_handler = $config->get('system', 'session_handler', self::HANDLER_DEFAULT);
				$handler = null;

				switch ($session_handler) {
					case self::HANDLER_DATABASE:
						$handler = new Session\Handler\Database($dba, $logger, $server);
						break;
					case self::HANDLER_CACHE:
						// In case we're using the db as cache driver, use the native db session, not the cache
						if ($config->get('system', 'cache_driver') === Cache::TYPE_DATABASE) {
							$handler = new Session\Handler\Database($dba, $logger, $server);
						} else {
							$handler = new Session\Handler\Cache($cache, $logger, $server);
						}
						break;
				}

				$session = new Session\Native($baseURL, $cookie, $handler);
			}
		} finally {
			$profiler->saveTimestamp($stamp1, 'parser', System::callstack());
			return $session;
		}
	}
}
