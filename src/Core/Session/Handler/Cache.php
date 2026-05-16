<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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

	#[\ReturnTypeWillChange]
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
	#[\ReturnTypeWillChange]
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

	#[\ReturnTypeWillChange]
	/**
	 * @return int|false
	 */
	public function gc($max_lifetime)
	{
		return 0; // Cache does not support garbage collection, so we return 0 to indicate no action taken
	}
}
