<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Handler;

use Friendica\Database\Database as DBA;
use Psr\Log\LoggerInterface;

/**
 * SessionHandler using database
 */
class Database extends AbstractSessionHandler
{
	/** @var DBA */
	private $dba;
	/** @var LoggerInterface */
	private $logger;
	/** @var array The $_SERVER variable */
	private $server;
	/** @var bool global check, if the current Session exists */
	private $sessionExists = false;

	/**
	 * DatabaseSessionHandler constructor.
	 *
	 * @param DBA             $dba
	 * @param LoggerInterface $logger
	 * @param array           $server
	 */
	public function __construct(DBA $dba, LoggerInterface $logger, array $server)
	{
		$this->dba    = $dba;
		$this->logger = $logger;
		$this->server = $server;
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
			$session = $this->dba->selectFirst('session', ['data'], ['sid' => $id]);
			if ($this->dba->isResult($session)) {
				$this->sessionExists = true;
				return $session['data'];
			}
		} catch (\Exception $exception) {
			$this->logger->warning('Cannot read session.', ['id' => $id, 'exception' => $exception]);
			return '';
		}

		$this->logger->notice('no data for session', ['session_id' => $id, 'uri' => $this->server['REQUEST_URI'] ?? '']);

		return '';
	}

	/**
	 * Standard PHP session write callback
	 *
	 * This callback updates the DB-stored session data and/or the expiration depending
	 * on the case. Uses the Session::expire global for existing session, 5 minutes
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

		$expire         = time() + static::EXPIRE;
		$default_expire = time() + 300;

		try {
			if ($this->sessionExists) {
				$fields    = ['data' => $data, 'expire' => $expire];
				$condition = ["`sid` = ? AND (`data` != ? OR `expire` != ?)", $id, $data, $expire];
				$this->dba->update('session', $fields, $condition);
			} else {
				$fields = ['sid' => $id, 'expire' => $default_expire, 'data' => $data];
				$this->dba->insert('session', $fields);
			}
		} catch (\Exception $exception) {
			$this->logger->warning('Cannot write session.', ['id' => $id, 'exception' => $exception]);
			return false;
		}

		return true;
	}

	public function close(): bool
	{
		return true;
	}

	public function destroy($id): bool
	{
		try {
			return $this->dba->delete('session', ['sid' => $id]);
		} catch (\Exception $exception) {
			$this->logger->warning('Cannot destroy session.', ['id' => $id, 'exception' => $exception]);
			return false;
		}
	}

	#[\ReturnTypeWillChange]
	/**
	 * @return int|false
	 */
	public function gc($max_lifetime)
	{
		try {
			$result = $this->dba->delete('session', ["`expire` < ?", time()]);
		} catch (\Exception $exception) {
			$this->logger->warning('Cannot use garbage collector.', ['exception' => $exception]);
			return false;
		}

		if ($result !== false) {
			// TODO: DBA::delete() returns true, but we need to return the number of deleted rows as interger
			$result = 0;
		}

		return $result;
	}
}
