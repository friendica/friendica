<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;

class ItemURI
{
	/**
	 * Insert an item-uri record and return its id
	 *
	 * @param array $fields Item-uri fields
	 * @return int|null item-uri id
	 * @throws \Exception
	 */
	public static function insert(array $fields)
	{
		$fields = DI::dbaDefinition()->truncateFieldsForTable('item-uri', $fields);

		if (!DBA::exists('item-uri', ['uri' => $fields['uri']])) {
			DBA::insert('item-uri', $fields, Database::INSERT_IGNORE);
		}

		$itemuri = DBA::selectFirst('item-uri', ['id', 'guid'], ['uri' => $fields['uri']]);
		if (!DBA::isResult($itemuri)) {
			// This shouldn't happen
			DI::logger()->warning('Item-uri not found', $fields);
			return null;
		}

		if (empty($itemuri['guid']) && !empty($fields['guid'])) {
			DBA::update('item-uri', ['guid' => $fields['guid']], ['id' => $itemuri['id']]);
		}

		return $itemuri['id'];
	}

	/**
	 * Searched for an id of a given uri. Adds it, if not existing yet.
	 *
	 * @param string $uri
	 * @param bool   $insert
	 *
	 * @return integer item-uri id
	 *
	 * @throws \Exception
	 */
	public static function getIdByURI(string $uri, bool $insert = true): int
	{
		if (empty($uri)) {
			return 0;
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $uri]);

		if (!DBA::isResult($itemuri) && $insert) {
			return self::insert(['uri' => $uri]);
		}

		return $itemuri['id'] ?? 0;
	}

	/**
	 * @param int $uriId
	 * @return bool
	 * @throws \Exception
	 */
	public static function exists(int $uriId): bool
	{
		return DBA::exists('item-uri', ['id' => $uriId]);
	}
}
