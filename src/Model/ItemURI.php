<?php

/**
 * @file src/Model/ItemURI.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Database\DBA;

require_once 'boot.php';

class ItemURI extends BaseObject
{
	/**
	 * @brief Insert an item-uri record and return its id
	 *
	 * @param array $fields Item-uri fields
	 *
	 * @return integer item-uri id
	 */
	public static function insert($fields)
	{
		if (!DBA::exists('item-uri', ['uri' => $fields['uri']])) {
			DBA::insert('item-uri', $fields, true);
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $fields['uri']]);

		if (!$itemuri::isResult($itemuri)) {
			// This shouldn't happen
			return null;
		}

		return $itemuri['id'];
	}

	/**
	 * @brief Searched for an id of a given uri. Adds it, if not existing yet.
	 *
	 * @param string $uri
	 *
	 * @return integer item-uri id
	 */
	public static function getIdByURI($uri)
	{
		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $fields['uri']]);

		if (!$itemuri::isResult($itemuri)) {
			return self::insert(['uri' => $uri]);
		}

		return $itemuri['id'];
	}
}
