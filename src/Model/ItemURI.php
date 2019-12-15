<?php

/**
 * @file src/Model/ItemURI.php
 */

namespace Friendica\Model;

use Friendica\Database\DBA;

class ItemURI
{
	/**
	 * @brief Insert an item-uri record and return its id
	 *
	 * @param array $fields Item-uri fields
	 *
	 * @return integer item-uri id
	 * @throws \Exception
	 */
	public static function insert($fields)
	{
		// If the URI gets too long we only take the first parts and hope for best
		$uri = substr($fields['uri'], 0, 255);

		if (!DBA::exists('item-uri', ['uri' => $uri])) {
			DBA::insert('item-uri', $fields, true);
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $uri]);

		if (!DBA::isResult($itemuri)) {
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
	 * @throws \Exception
	 */
	public static function getIdByURI($uri)
	{
		// If the URI gets too long we only take the first parts and hope for best
		$uri = substr($uri, 0, 255);

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $uri]);

		if (!DBA::isResult($itemuri)) {
			return self::insert(['uri' => $uri]);
		}

		return $itemuri['id'];
	}
}
