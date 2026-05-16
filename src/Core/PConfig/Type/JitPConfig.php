<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\PConfig\Type;

use Friendica\Core\PConfig\Repository;
use Friendica\Core\PConfig\ValueObject;

/**
 * This class implements the Just-In-Time configuration, which will cache
 * user config values in a cache, once they are retrieved.
 *
 * Default Configuration type.
 * Provides the best performance for pages loading few configuration variables.
 */
class JitPConfig extends AbstractPConfigValues
{
	public const NAME = 'jit';

	/**
	 * @var array Array of already loaded db values (even if there was no value)
	 */
	private $db_loaded;

	/**
	 * @param ValueObject\Cache  $configCache The configuration cache
	 * @param Repository\PConfig $configRepo  The configuration model
	 */
	public function __construct(ValueObject\Cache $configCache, Repository\PConfig $configRepo)
	{
		parent::__construct($configCache, $configRepo);
		$this->db_loaded = [];
	}

	/**
	 * {@inheritDoc}
	 *
	 */
	public function load(int $uid, string $cat = 'config'): array
	{
		// If not connected or no uid, do nothing
		if (!$uid || !$this->configModel->isConnected()) {
			return [];
		}

		$config = $this->configModel->load($uid, $cat);

		if (!empty($config[$cat])) {
			foreach ($config[$cat] as $key => $value) {
				$this->db_loaded[$uid][$cat][$key] = true;
			}
		}

		// load the whole category out of the DB into the cache
		$this->configCache->load($uid, $config);

		return $config;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(int $uid, string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		if (!$uid) {
			return $default_value;
		}

		// if the value isn't loaded or refresh is needed, load it to the cache
		if ($this->configModel->isConnected()
			&& (empty($this->db_loaded[$uid][$cat][$key]) || $refresh)) {
			$dbValue = $this->configModel->get($uid, $cat, $key);

			if (isset($dbValue)) {
				$this->configCache->set($uid, $cat, $key, $dbValue);
				unset($dbValue);
			}

			$this->db_loaded[$uid][$cat][$key] = true;
		}

		// use the config cache for return
		$result = $this->configCache->get($uid, $cat, $key);

		return $result ?? $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(int $uid, string $cat, string $key, $value): bool
	{
		if (!$uid) {
			return false;
		}

		// set the cache first
		$cached = $this->configCache->set($uid, $cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($uid, $cat, $key, $value);

		$this->db_loaded[$uid][$cat][$key] = $stored;

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(int $uid, string $cat, string $key): bool
	{
		if (!$uid) {
			return false;
		}

		$cacheRemoved = $this->configCache->delete($uid, $cat, $key);

		if (isset($this->db_loaded[$uid][$cat][$key])) {
			unset($this->db_loaded[$uid][$cat][$key]);
		}

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($uid, $cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
