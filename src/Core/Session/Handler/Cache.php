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

namespace Friendica\Core\Session\Handler;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Psr\Log\LoggerInterface;

/**
 * SessionHandler using Friendica Cache
 */
class Cache extends AbstractSessionHandler
{
	/** @var ICanCache */
	private $cache;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(ICanCache $cache, LoggerInterface $logger)
	{
		$this->cache  = $cache;
		$this->logger = $logger;
	}

	public function open($path, $name): bool
	{
		return true;
	}

	public function read($id)
	{
		if (empty($id)) {
			return '';
		}

		try {
			$data = $this->cache->get('session:' . $id);
			if (!empty($data)) {
				return $data;
			}
		} catch (CachePersistenceException $exception) {
			$this->logger->warning('Cannot read session.', ['id' => $id, 'exception' => $exception]);
			return '';
		}

		return '';
	}

	/**
	 * Standard PHP session write callback
	 *
	 * This callback updates the stored session data and/or the expiration depending
	 * on the case. Uses the Session::expire for existing session, 5 minutes
	 * for newly created session.
	 *
	 * @param string $id   Session ID with format: [a-z0-9]{26}
	 * @param string $data Serialized session data
	 *
	 * @return bool Returns false if parameters are missing, true otherwise
	 */
	public function write($id, $data): bool
	{
		if (!$id) {
			return false;
		}

		if (!$data) {
			return $this->destroy($id);
		}

		try {
			return $this->cache->set('session:' . $id, $data, static::EXPIRE);
		} catch (CachePersistenceException $exception) {
			$this->logger->warning('Cannot write session', ['id' => $id, 'exception' => $exception]);
			return false;
		}
	}

	public function close(): bool
	{
		return true;
	}

	public function destroy($id): bool
	{
		try {
			return $this->cache->delete('session:' . $id);
		} catch (CachePersistenceException $exception) {
			$this->logger->warning('Cannot destroy session', ['id' => $id, 'exception' => $exception]);
			return false;
		}
	}

	public function gc($max_lifetime): bool
	{
		return true;
	}
}
