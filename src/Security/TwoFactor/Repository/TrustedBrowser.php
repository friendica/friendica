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

namespace Friendica\Security\TwoFactor\Repository;

use Friendica\Security\TwoFactor;
use Friendica\Database\Database;
use Friendica\Security\TwoFactor\Exception\TrustedBrowserNotFoundException;
use Friendica\Security\TwoFactor\Exception\TrustedBrowserPersistenceException;
use Psr\Log\LoggerInterface;

class TrustedBrowser
{
	/** @var Database  */
	protected $db;

	/** @var LoggerInterface  */
	protected $logger;

	/** @var TwoFactor\Factory\TrustedBrowser  */
	protected $factory;

	protected static $table_name = '2fa_trusted_browser';

	public function __construct(Database $database, LoggerInterface $logger, TwoFactor\Factory\TrustedBrowser $factory = null)
	{
		$this->db      = $database;
		$this->logger  = $logger;
		$this->factory = $factory ?? new TwoFactor\Factory\TrustedBrowser($logger);
	}

	/**
	 * @param string $cookie_hash
	 *
	 * @return TwoFactor\Model\TrustedBrowser|null
	 *
	 * @throws TrustedBrowserPersistenceException
	 * @throws TrustedBrowserNotFoundException
	 */
	public function selectOneByHash(string $cookie_hash): TwoFactor\Model\TrustedBrowser
	{
		try {
			$fields = $this->db->selectFirst(self::$table_name, [], ['cookie_hash' => $cookie_hash]);
		} catch (\Exception $exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Internal server error when retrieving cookie hash \'%s\'', $cookie_hash));
		}
		if (!$this->db->isResult($fields)) {
			throw new TrustedBrowserNotFoundException(sprintf('Cookie hash \'%s\' not found', $cookie_hash));
		}

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @param int $uid
	 *
	 * @return TwoFactor\Collection\TrustedBrowsers
	 *
	 * @throws TrustedBrowserPersistenceException
	 */
	public function selectAllByUid(int $uid): TwoFactor\Collection\TrustedBrowsers
	{
		try {
			$rows = $this->db->selectToArray(self::$table_name, [], ['uid' => $uid]);

			$trustedBrowsers = [];
			foreach ($rows as $fields) {
				$trustedBrowsers[] = $this->factory->createFromTableRow($fields);
			}
			return new TwoFactor\Collection\TrustedBrowsers($trustedBrowsers);

		} catch (\Exception $exception) {
			throw new TrustedBrowserPersistenceException(sprintf('selection for uid \'%s\' wasn\'t successful.', $uid));
		}
	}

	/**
	 * @param TwoFactor\Model\TrustedBrowser $trustedBrowser
	 *
	 * @return bool
	 *
	 * @throws TrustedBrowserPersistenceException
	 */
	public function save(TwoFactor\Model\TrustedBrowser $trustedBrowser): bool
	{
		try {
			return $this->db->insert(self::$table_name, $trustedBrowser->toArray(), $this->db::INSERT_UPDATE);
		} catch (\Exception $exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t save trusted Browser with cookie_hash \'%s\'', $trustedBrowser->cookie_hash));
		}
	}

	/**
	 * @param TwoFactor\Model\TrustedBrowser $trustedBrowser
	 *
	 * @return bool
	 *
	 * @throws TrustedBrowserPersistenceException
	 */
	public function remove(TwoFactor\Model\TrustedBrowser $trustedBrowser): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['cookie_hash' => $trustedBrowser->cookie_hash]);
		} catch (\Exception $exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t delete trusted Browser with cookie hash \'%s\'', $trustedBrowser->cookie_hash));
		}
	}

	/**
	 * @param int    $local_user
	 * @param string $cookie_hash
	 *
	 * @return bool
	 *
	 * @throws TrustedBrowserPersistenceException
	 */
	public function removeForUser(int $local_user, string $cookie_hash): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['cookie_hash' => $cookie_hash, 'uid' => $local_user]);
		} catch (\Exception $exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t delete trusted Browser for user \'%s\' and cookie hash \'%s\'', $local_user, $cookie_hash));
		}
	}

	/**
	 * @param int $local_user
	 * @return bool
	 */
	public function removeAllForUser(int $local_user): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['uid' => $local_user]);
		} catch (\Exception $exception) {
			throw new TrustedBrowserPersistenceException(sprintf('Couldn\'t delete trusted Browsers for user \'%s\'', $local_user));
		}
	}
}
