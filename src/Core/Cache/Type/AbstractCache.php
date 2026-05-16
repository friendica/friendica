<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Capability\ICanCache;

/**
 * Abstract class for common used functions
 */
abstract class AbstractCache implements ICanCache
{
	public const NAME = '';

	/**
	 * @var string The hostname
	 */
	private $hostName;

	public function __construct(string $hostName)
	{
		$this->hostName = $hostName;
	}

	/**
	 * Returns the prefix (to avoid namespace conflicts)
	 *
	 * @return string
	 */
	protected function getPrefix(): string
	{
		// We fetch with the hostname as key to avoid problems with other applications
		return $this->hostName;
	}

	/**
	 * @param string $key The original key
	 *
	 * @return string        The cache key used for the cache
	 */
	protected function getCacheKey(string $key): string
	{
		return $this->getPrefix() . ":" . $key;
	}

	/**
	 * @param string[] $keys A list of cached keys
	 *
	 * @return string[] A list of original keys
	 */
	protected function getOriginalKeys(array $keys): array
	{
		if (empty($keys)) {
			return [];
		} else {
			// Keys are prefixed with the node hostname, let's remove it
			array_walk($keys, function (&$value) {
				$value = preg_replace('/^' . $this->hostName . ':/', '', $value);
			});

			sort($keys);

			return $keys;
		}
	}

	/**
	 * Filters the keys of an array with a given prefix
	 * Returns the filtered keys as an new array
	 *
	 * @param string[]    $keys   The keys, which should get filtered
	 * @param string|null $prefix The prefix (if null, all keys will get returned)
	 *
	 * @return string[] The filtered array with just the keys
	 */
	protected function filterArrayKeysByPrefix(array $keys, string $prefix = null): array
	{
		if (empty($prefix)) {
			return $keys;
		} else {
			$result = [];

			foreach ($keys as $key) {
				if (str_starts_with($key, $prefix)) {
					array_push($result, $key);
				}
			}

			return $result;
		}
	}

	/** {@inheritDoc} */
	public function getName(): string
	{
		return static::NAME;
	}
}
