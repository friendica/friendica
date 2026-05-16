<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use BadMethodCallException;

class DeliveryData
{
	public const LEGACY_FIELD_LIST = [
		// Legacy fields moved from item table
		'postopts',
		'inform',
	];

	public const FIELD_LIST = [
		// New delivery fields with virtual field name in item fields
		'queue_count'  => 'delivery_queue_count',
		'queue_done'   => 'delivery_queue_done',
		'queue_failed' => 'delivery_queue_failed',
	];

	public const ACTIVITYPUB = 1;
	public const DFRN        = 2;
	public const LEGACY_DFRN = 3; // @deprecated 2021.09 since version 2021.09
	public const DIASPORA    = 4;
	public const OSTATUS     = 5; // @deprecated 2024.09 since version 2024.09
	public const MAIL        = 6;

	/**
	 * Extract delivery data from the provided item fields
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function extractFields(array &$fields)
	{
		$delivery_data = [];
		foreach (array_merge(self::FIELD_LIST, self::LEGACY_FIELD_LIST) as $key => $field) {
			if (is_int($key) && isset($fields[$field])) {
				// Legacy field moved from item table
				$delivery_data[$field] = $fields[$field];
				$fields[$field]        = null;
			} elseif (isset($fields[$field])) {
				// New delivery field with virtual field name in item fields
				$delivery_data[$key] = $fields[$field];
				unset($fields[$field]);
			}
		}

		return $delivery_data;
	}

	/**
	 * Increments the queue_done for the given URI ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $uri_id
	 * @param integer $protocol
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueDone(int $uri_id, int $protocol = 0)
	{
		$increments = ["`queue_done` = `queue_done` + 1"];

		switch ($protocol) {
			case self::ACTIVITYPUB:
				$increments[] = "`activitypub` = `activitypub` + 1";
				break;
			case self::DFRN:
				$increments[] = "`dfrn` = `dfrn` + 1";
				break;
			case self::LEGACY_DFRN:
				$increments[] = "`legacy_dfrn` = `legacy_dfrn` + 1";
				break;
			case self::DIASPORA:
				$increments[] = "`diaspora` = `diaspora` + 1";
				break;
		}

		return DBA::update('post-delivery-data', $increments, ['uri-id' => $uri_id]);
	}

	/**
	 * Increments the queue_failed for the given URI ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $uri_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueFailed(int $uri_id)
	{
		return DBA::update('post-delivery-data', ["`queue_failed` = `queue_failed` + 1"], ['uri-id' => $uri_id]);
	}

	/**
	 * Increments the queue_count for the given URI ID.
	 *
	 * @param integer $uri_id
	 * @param integer $increment
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueCount(int $uri_id, int $increment = 1)
	{
		return DBA::update('post-delivery-data', ["`queue_count` = `queue_count` + $increment"], ['uri-id' => $uri_id]);
	}

	/**
	 * Insert a new URI delivery data entry
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, array $fields)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields['uri-id'] = $uri_id;

		return DBA::replace('post-delivery-data', $fields);
	}

	/**
	 * Update/Insert URI delivery data
	 *
	 * If you want to update queue_done, please use incrementQueueDone instead.
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, array $fields)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		if (empty($fields)) {
			// Nothing to do, update successful
			return true;
		}

		return DBA::update('post-delivery-data', $fields, ['uri-id' => $uri_id], true);
	}

	/**
	 * Delete URI delivery data
	 *
	 * @param integer $uri_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete(int $uri_id)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		return DBA::delete('post-delivery-data', ['uri-id' => $uri_id]);
	}
}
