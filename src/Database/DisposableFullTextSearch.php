<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Database;

/**
 * Full-text search on a haystack string that isn't present in the database.
 * The haystack is inserted in a temporary table with a FULLTEXT index, then any number of
 * matches can be performed on it before the row is deleted when the class instance is destroyed,
 * either manually or at the end of the script at the latest.
 */
class DisposableFullTextSearch
{
	private Database $db;
	/** @var int Unique identifier of the haystack in the database. */
	private int $identifier;

	public function __construct(Database $database, string $haystack)
	{
		$this->db = $database;

		// Unique identifier generation. Two DisposableFullTextSearch object should never have the same as the first object destruction
		// would delete both check-full-text-search rows before the second object destruction is called, leading to unexpected behavior.
		do {
			// Maximum value is indicated by the INT UNSIGNED type of the check-full-text-search.pid field
			$this->identifier = random_int(0, pow(2, 31) - 1);
		} while ($this->db->exists('check-full-text-search', ['pid' => $this->identifier]));

		// If the `exists()` call fails and return false because the database is unavailable, the `insert()` call will likely fail as well, which means
		// all subsequent calls to `match()` will return false because the haystack won't have been inserted.
		// However, at this point there may be bigger problems to worry about.
		$this->db->insert('check-full-text-search', ['pid' => $this->identifier, 'searchtext' => $haystack]);
	}

	public function __destruct()
	{
		$this->db->delete('check-full-text-search', ['pid' => $this->identifier]);
	}

	/**
	 * @param string $needle Boolean mode search string
	 * @return bool
	 * @throws \Exception
	 */
	public function match(string $needle): bool
	{
		return $this->db->exists('check-full-text-search', ["`pid` = ? AND MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", $this->identifier, $needle]);
	}
}
