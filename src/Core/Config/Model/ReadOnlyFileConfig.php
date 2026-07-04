<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Config\Model;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Exception\ConfigPersistenceException;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Creates a basic, readonly model for the file-based configuration
 */
class ReadOnlyFileConfig implements IManageConfigValues
{
	/** @var Cache */
	protected $configCache;

	/**
	 * @param Cache $configCache The configuration cache (based on the config-files)
	 */
	public function __construct(Cache $configCache)
	{
		$this->configCache = $configCache;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache(): Cache
	{
		return $this->configCache;
	}

	/**    {@inheritDoc} */
	public function beginTransaction(): ISetConfigValuesTransactionally
	{
		throw new ConfigPersistenceException('beginTransaction not allowed.');
	}

	/** {@inheritDoc} */
	public function reload(): never
	{
		throw new ConfigPersistenceException('reload not allowed.');
	}

	/** {@inheritDoc} */
	public function get(string $cat, ?string $key = null, $default_value = null)
	{
		return $this->configCache->get($cat, $key) ?? $default_value;
	}

	/** {@inheritDoc} */
	public function isWritable(string $cat, string $key): bool
	{
		return $this->configCache->getSource($cat, $key) < Cache::SOURCE_ENV;
	}

	/** {@inheritDoc} */
	public function set(string $cat, string $key, $value): bool
	{
		throw new ConfigPersistenceException('set not allowed.');
	}

	/** {@inheritDoc} */
	public function delete(string $cat, string $key): bool
	{
		throw new ConfigPersistenceException('Save not allowed');
	}
}
