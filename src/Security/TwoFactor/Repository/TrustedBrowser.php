<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\TwoFactor\Repository;

use Friendica\Database\Database;
use Friendica\Security\TwoFactor\Exception\TrustedBrowserNotFoundException;
use Friendica\Security\TwoFactor\Exception\TrustedBrowserPersistenceException;
use Friendica\Security\TwoFactor\Collection\TrustedBrowsers as TrustedBrowsersCollection;
use Friendica\Security\TwoFactor\Factory\TrustedBrowser as TrustedBrowserFactory;
use Friendica\Security\TwoFactor\Model\TrustedBrowser as TrustedBrowserModel;
use Psr\Log\LoggerInterface;

class TrustedBrowser
{
	/** @var Database  */
	protected $db;

	/** @var LoggerInterface  */
	protected $logger;

	/** @var TrustedBrowserFactory  */
	protected $factory;

	protected static $table_name = '2fa_trusted_browser';

	public function __construct(
		Database $database,
		LoggerInterface $logger,
		?TrustedBrowserFactory $factory = null,
	) {
		$this->db      = $database;
		$this->logger  = $logger;
		$this->factory = $factory ?? new TrustedBrowserFactory($logger);
	}

	/**
	 * @param string $cookie_hash
	 *
	 * @return TrustedBrowserModel
	 *
	 * @throws TrustedBrowserPersistenceException
	 * @throws TrustedBrowserNotFoundException
	 */
	public function selectOneByHash(string $cookie_hash): TrustedBrowserModel
	{
		try {
			$fields = $this->db->selectFirst(self::$table_name, [], ['cookie_hash' => $cookie_hash]);
		} catch (\Exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Internal server error when retrieving cookie hash \'%s\'', $cookie_hash));
		}
		if (!$this->db->isResult($fields)) {
			throw new TrustedBrowserNotFoundException(sprintf('Cookie hash \'%s\' not found', $cookie_hash));
		}

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @throws TrustedBrowserPersistenceException
	 */
	public function selectAllByUid(int $uid): TrustedBrowsersCollection
	{
		try {
			$rows = $this->db->selectToArray(self::$table_name, [], ['uid' => $uid]);

			$trustedBrowsers = [];
			foreach ($rows as $fields) {
				$trustedBrowsers[] = $this->factory->createFromTableRow($fields);
			}
			return new TrustedBrowsersCollection($trustedBrowsers);

		} catch (\Exception) {
			throw new TrustedBrowserPersistenceException(sprintf('selection for uid \'%s\' wasn\'t successful.', $uid));
		}
	}

	/**
	 * @throws TrustedBrowserPersistenceException
	 */
	public function save(TrustedBrowserModel $trustedBrowser): bool
	{
		try {
			return $this->db->insert(self::$table_name, $trustedBrowser->toArray(), $this->db::INSERT_UPDATE);
		} catch (\Exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t save trusted Browser with cookie_hash \'%s\'', $trustedBrowser->cookie_hash));
		}
	}

	/**
	 * @throws TrustedBrowserPersistenceException
	 */
	public function remove(TrustedBrowserModel $trustedBrowser): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['cookie_hash' => $trustedBrowser->cookie_hash]);
		} catch (\Exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t delete trusted Browser with cookie hash \'%s\'', $trustedBrowser->cookie_hash));
		}
	}

	/**
	 * @throws TrustedBrowserPersistenceException
	 */
	public function removeForUser(int $local_user, string $cookie_hash): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['cookie_hash' => $cookie_hash, 'uid' => $local_user]);
		} catch (\Exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t delete trusted Browser for user \'%s\' and cookie hash \'%s\'', $local_user, $cookie_hash));
		}
	}

	public function removeAllForUser(int $local_user): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['uid' => $local_user]);
		} catch (\Exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t delete trusted Browsers for user \'%s\'', $local_user));
		}
	}
}
