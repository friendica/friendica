<?php

/**
 * @file src/Model/ItemDeliveryData.php
 */

namespace Friendica\Model;

use Friendica\Database\DBA;

class ItemDeliveryData
{
	const LEGACY_FIELD_LIST = [
		// Legacy fields moved from item table
		'postopts',
		'inform',
	];

	const FIELD_LIST = [
		// New delivery fields with virtual field name in item fields
		'queue_count' => 'delivery_queue_count',
		'queue_done'  => 'delivery_queue_done',
	];

	/**
	 * Extract delivery data from the provided item fields
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function extractFields(array &$fields)
	{
		$delivery_data = [];
		foreach (ItemDeliveryData::FIELD_LIST as $key => $field) {
			if (is_int($key) && isset($fields[$field])) {
				// Legacy field moved from item table
				$delivery_data[$field] = $fields[$field];
				$fields[$field] = null;
			} elseif (isset($fields[$field])) {
				// New delivery field with virtual field name in item fields
				$delivery_data[$key] = $fields[$field];
				unset($fields[$field]);
			}
		}

		return $delivery_data;
	}

	/**
	 * Increments the queue_done for the given item ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $item_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueDone($item_id)
	{
		return DBA::e('UPDATE `item-delivery-data` SET `queue_done` = `queue_done` + 1 WHERE `iid` = ?', $item_id);
	}

	/**
	 * Insert a new item delivery data entry
	 *
	 * @param integer $item_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert($item_id, array $fields)
	{
		if (empty($item_id)) {
			throw new \BadMethodCallException('Empty item_id');
		}

		$fields['iid'] = $item_id;

		return DBA::insert('item-delivery-data', $fields);
	}

	/**
	 * Update/Insert item delivery data
	 *
	 * If you want to update queue_done, please use incrementQueueDone instead.
	 *
	 * @param integer $item_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function update($item_id, array $fields)
	{
		if (empty($item_id)) {
			throw new \BadMethodCallException('Empty item_id');
		}

		if (empty($fields)) {
			// Nothing to do, update successful
			return true;
		}

		return DBA::update('item-delivery-data', $fields, ['iid' => $item_id], true);
	}

	/**
	 * Delete item delivery data
	 *
	 * @param integer $item_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete($item_id)
	{
		if (empty($item_id)) {
			throw new \BadMethodCallException('Empty item_id');
		}

		return DBA::delete('item-delivery-data', ['iid' => $item_id]);
	}
}
