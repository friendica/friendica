<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\PConfig\Type;

use Friendica\Core\PConfig\Repository;
use Friendica\Core\PConfig\ValueObject;

/**
 * This class implements the preload configuration, which will cache
 * all user config values per call in a cache.
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 */
class PreloadPConfig extends AbstractPConfigValues
{
	public const NAME = 'preload';

	/** @var array */
	private $config_loaded;

	/**
	 * @param ValueObject\Cache  $configCache The configuration cache
	 * @param Repository\PConfig $configRepo  The configuration model
	 */
	public function __construct(ValueObject\Cache $configCache, Repository\PConfig $configRepo)
	{
		parent::__construct($configCache, $configRepo);
		$this->config_loaded = [];
	}

	/**
	 * {@inheritDoc}
	 *
	 * This loads all config values everytime load is called
	 *
	 */
	public function load(int $uid, string $cat = 'config'): array
	{
		// Don't load the whole configuration twice or with invalid uid
		if (!$uid || !empty($this->config_loaded[$uid])) {
			return [];
		}

		// If not connected, do nothing
		if (!$this->configModel->isConnected()) {
			return [];
		}

		$config                    = $this->configModel->load($uid);
		$this->config_loaded[$uid] = true;

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

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		} elseif ($refresh) {
			if ($this->configModel->isConnected()) {
				$config = $this->configModel->get($uid, $cat, $key);
				if (isset($config)) {
					$this->configCache->set($uid, $cat, $key, $config);
				}
			}
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

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		}

		// set the cache first
		$cached = $this->configCache->set($uid, $cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($uid, $cat, $key, $value);

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

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		}

		$cacheRemoved = $this->configCache->delete($uid, $cat, $key);

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($uid, $cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
