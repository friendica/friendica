<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database\Model;

use Exception;
use Friendica\Database\DBA;
use Friendica\Entity\CacheEntity;

/**
 * Model for a row in the cache table
 */
final class CacheModel implements CacheEntity
{
	/**
	 * Validates the row array and creates the entity
	 *
	 * @throws Exception If $data['v'] contains invalid data that could not unserialize.
	 */
	public static function createFromArray(array $data): self
	{
		$rawValue = array_key_exists('v', $data) ? (string) $data['v'] : '';
		$value = @unserialize($rawValue);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($value === false && $rawValue !== 'b:0;' ) {
			throw new Exception(sprintf('Invalid value data for cache object.'));
		}

		$entity = new self();
		array_key_exists('k', $data) ?? $entity->k = (string) $data['k'];
		$entity->v = $rawValue;
		$entity->value = $value;
		array_key_exists('expired', $data) ?? $entity->expired = (string) $data['expired'];
		array_key_exists('updated', $data) ?? $entity->updated = (string) $data['updated'];

		return $entity;
	}

	/**
	 * cache key
	 */
	private string $k = '';

	/**
	 * cached serialized value
	 */
	private string $v = '';

	/**
	 *
	 * @var mixed $value cached unserialized value
	 */
	private $value;

	/**
	 * datetime of cache expiration
	 */
	private string $expired = DBA::NULL_DATETIME;

	/**
	 * datetime of cache insertion
	 */
	private string $updated = DBA::NULL_DATETIME;

	private function __construct() {}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
}
